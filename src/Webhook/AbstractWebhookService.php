<?php

declare(strict_types=1);

/**
 * Copyright (c) 2022 Coincharge
 * This file is open source and available under the MIT license.
 * See the LICENSE file for more info.
 *
 * Author: Coincharge<shopware@coincharge.io>
 */

namespace Coincharge\Shopware\Webhook;

use Coincharge\Shopware\Client\ApiRequestException;
use Coincharge\Shopware\Client\ClientInterface;
use Coincharge\Shopware\Configuration\ConfigurationService;
use Coincharge\Shopware\Order\OrderService;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Framework\Context;
use Shopware\Core\System\StateMachine\StateMachineException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

// The webhook only says which invoice changed; every decision comes from the invoice the API returns.
abstract class AbstractWebhookService implements WebhookServiceInterface
{
    // States a webhook may move forward; paid, refunded or cancelled transactions never change.
    private const OPEN_STATES = [
        OrderTransactionStates::STATE_OPEN,
        OrderTransactionStates::STATE_UNCONFIRMED,
        OrderTransactionStates::STATE_IN_PROGRESS,
    ];
    private const PAYABLE_STATES = [
        OrderTransactionStates::STATE_OPEN,
        OrderTransactionStates::STATE_UNCONFIRMED,
        OrderTransactionStates::STATE_IN_PROGRESS,
        OrderTransactionStates::STATE_PARTIALLY_PAID,
        OrderTransactionStates::STATE_FAILED,
    ];

    protected ClientInterface $client;
    protected ConfigurationService $configurationService;
    private OrderTransactionStateHandler $transactionStateHandler;
    private OrderService $orderService;
    private ProcessedInvoices $processedInvoices;
    private WebhookLog $webhookLog;
    protected LoggerInterface $logger;

    public function __construct(
        ClientInterface $client,
        ConfigurationService $configurationService,
        OrderTransactionStateHandler $transactionStateHandler,
        OrderService $orderService,
        ProcessedInvoices $processedInvoices,
        WebhookLog $webhookLog,
        LoggerInterface $logger
    ) {
        $this->client = $client;
        $this->configurationService = $configurationService;
        $this->transactionStateHandler = $transactionStateHandler;
        $this->orderService = $orderService;
        $this->processedInvoices = $processedInvoices;
        $this->webhookLog = $webhookLog;
        $this->logger = $logger;
    }

    abstract protected function provider(): string;

    abstract protected function setting(string $name): string;

    // Null when the request is signed with the stored secret, otherwise the reason.
    abstract protected function signatureProblem(Request $request, string $secret): ?string;

    // One of the InvoiceDecision constants.
    abstract protected function decide(array $invoice, float $price, string $currency): string;

    // Event types in which the webhook body claims the invoice is paid.
    abstract protected function paidEventTypes(): array;

    public function register(Request $request, ?string $salesChannelId): bool
    {
        try {
            if ($this->isEnabled()) {
                return true;
            }
        } catch (ApiRequestException $e) {
            // An API error is not "no webhook": creating one now would replace a working secret.
            $this->logger->error($this->provider() . ' webhook check failed, keeping the current webhook');

            return false;
        }

        try {
            $webhookUrl = $request->server->get('APP_URL') . '/api/_action/coincharge/webhook-endpoint';
            $body = $this->client->sendPostRequest($this->storePath() . '/webhooks', ['url' => $webhookUrl]);
        } catch (ApiRequestException $e) {
            $this->logger->error($this->provider() . ' webhook could not be created');

            return false;
        }

        if (empty($body['secret']) || empty($body['id'])) {
            $this->logger->error($this->provider() . ' webhook could not be created');

            return false;
        }

        $this->configurationService->setSetting($this->setting('webhookSecret'), $body['secret']);
        $this->configurationService->setSetting($this->setting('webhookId'), $body['id']);
        $this->logger->info($this->provider() . ' webhook created');

        return true;
    }

    // Throws ApiRequestException when the API can't tell whether the webhook exists.
    public function isEnabled(): bool
    {
        $webhookId = (string) $this->configurationService->getSetting($this->setting('webhookId'));
        if ($webhookId === '') {
            return false;
        }

        try {
            $response = $this->client->sendGetRequest($this->storePath() . '/webhooks/' . rawurlencode($webhookId));
        } catch (ApiRequestException $e) {
            if ($e->isNotFound()) {
                return false;
            }
            throw $e;
        }

        return !empty($response) && ($response['enabled'] ?? false) === true;
    }

    public function process(Request $request, Context $context): Response
    {
        $secret = (string) $this->configurationService->getSetting($this->setting('webhookSecret'));
        $problem = $this->signatureProblem($request, $secret);
        if ($problem !== null) {
            $this->webhookLog->security($this->provider(), $problem);

            return new Response('Unauthorized', Response::HTTP_UNAUTHORIZED);
        }

        $body = json_decode($request->getContent(), true);
        $invoiceId = is_array($body) && is_string($body['invoiceId'] ?? null) ? $body['invoiceId'] : '';
        if ($invoiceId === '') {
            $this->webhookLog->info($this->provider(), 'no invoice id');

            return new Response('', Response::HTTP_OK);
        }
        $claimsPaid = in_array($body['type'] ?? '', $this->paidEventTypes(), true);

        try {
            $invoice = $this->client->sendGetRequest($this->storePath() . '/invoices/' . rawurlencode($invoiceId));
        } catch (ApiRequestException $e) {
            if ($e->isNotFound()) {
                // The dashboard's "Send test webhook" button lands here.
                $this->webhookLog->info($this->provider(), 'unknown invoice', $invoiceId);

                return new Response('', Response::HTTP_OK);
            }
            $this->webhookLog->info($this->provider(), 'retry: API unavailable', $invoiceId);

            return new Response('', Response::HTTP_SERVICE_UNAVAILABLE);
        }

        $transaction = $this->findTransaction($invoice, $invoiceId, $context);
        if ($transaction === null) {
            return new Response('', Response::HTTP_OK);
        }

        $order = $transaction->getOrder();
        $decision = $this->decide(
            $invoice,
            $transaction->getAmount()->getTotalPrice(),
            (string) $order?->getCurrency()?->getIsoCode()
        );

        if ($decision !== InvoiceDecision::PAID && ($claimsPaid || $decision === InvoiceDecision::MISMATCH)) {
            $this->webhookLog->security($this->provider(), $claimsPaid ? 'forged status' : 'amount mismatch', $invoiceId);

            return new Response('', Response::HTTP_OK);
        }

        $this->apply($decision, $transaction, $invoiceId, $context);

        return new Response('', Response::HTTP_OK);
    }

    protected function storePath(): string
    {
        return '/api/v1/stores/' . rawurlencode((string) $this->configurationService->getSetting($this->setting('storeId')));
    }

    // The transaction this plugin created the invoice for, or null when the invoice isn't ours.
    private function findTransaction(array $invoice, string $invoiceId, Context $context): ?OrderTransactionEntity
    {
        $metadata = is_array($invoice['metadata'] ?? null) ? $invoice['metadata'] : [];
        $transactionId = is_string($metadata['transactionId'] ?? null) ? $metadata['transactionId'] : '';
        $transaction = $transactionId !== '' ? $this->orderService->getTransaction($transactionId, $context) : null;

        if ($transaction === null || $transaction->getOrder()?->getOrderNumber() !== ($metadata['orderNumber'] ?? null)) {
            $this->webhookLog->info($this->provider(), 'not ours', $invoiceId);

            return null;
        }

        // Transactions from before 1.2.0 have no list; the amount check still applies to them.
        $knownIds = $transaction->getCustomFields()[OrderService::INVOICE_IDS_FIELD] ?? null;
        if (is_array($knownIds) && !in_array($invoiceId, $knownIds, true)) {
            $this->webhookLog->security($this->provider(), 'invoice not created for this order', $invoiceId);

            return null;
        }

        return $transaction;
    }

    private function apply(string $decision, OrderTransactionEntity $transaction, string $invoiceId, Context $context): void
    {
        $state = (string) $transaction->getStateMachineState()?->getTechnicalName();
        $transactionId = $transaction->getId();

        if ($decision === InvoiceDecision::NONE) {
            $this->webhookLog->info($this->provider(), 'no change', $invoiceId);

            return;
        }

        if ($decision === InvoiceDecision::PAID) {
            $this->markPaid($transaction, $state, $invoiceId, $context);

            return;
        }

        // Only an unpaid transaction moves; a webhook never cancels or downgrades a paid one.
        $allowed = $decision === InvoiceDecision::PROCESS
            ? in_array($state, [OrderTransactionStates::STATE_OPEN, OrderTransactionStates::STATE_UNCONFIRMED], true)
            : in_array($state, self::OPEN_STATES, true);
        if (!$allowed) {
            $this->webhookLog->info($this->provider(), 'no change from ' . $state, $invoiceId);

            return;
        }

        $this->transition($invoiceId, fn () => match ($decision) {
            InvoiceDecision::PROCESS => $this->transactionStateHandler->process($transactionId, $context),
            InvoiceDecision::PARTIAL => $this->transactionStateHandler->payPartially($transactionId, $context),
            InvoiceDecision::FAIL => $this->transactionStateHandler->fail($transactionId, $context),
            default => throw new \LogicException('Unexpected invoice decision ' . $decision),
        });
        $this->orderService->setOrderStatus($transaction->getOrderId(), $invoiceId, $decision, $context);
        $this->webhookLog->info($this->provider(), 'status ' . $decision, $invoiceId);
    }

    private function markPaid(OrderTransactionEntity $transaction, string $state, string $invoiceId, Context $context): void
    {
        if (!in_array($state, self::PAYABLE_STATES, true)) {
            $this->webhookLog->info($this->provider(), 'already processed', $invoiceId);

            return;
        }

        $transactionId = $transaction->getId();
        try {
            $marked = $this->processedInvoices->markPaidOnce(
                $invoiceId,
                $transactionId,
                fn () => $this->transactionStateHandler->paid($transactionId, $context)
            );
        } catch (StateMachineException $e) {
            // Rolled back, so the invoice stays unclaimed.
            $this->webhookLog->info($this->provider(), 'transition not allowed', $invoiceId);

            return;
        }
        if (!$marked) {
            $this->webhookLog->info($this->provider(), 'already processed', $invoiceId);

            return;
        }

        $this->orderService->setOrderStatus($transaction->getOrderId(), $invoiceId, 'settled', $context);
        $this->webhookLog->info($this->provider(), 'paid', $invoiceId);
    }

    private function transition(string $invoiceId, callable $transition): void
    {
        try {
            $transition();
        } catch (StateMachineException $e) {
            $this->webhookLog->info($this->provider(), 'transition not allowed', $invoiceId);
        }
    }
}
