<?php

declare(strict_types=1);

namespace Coincharge\Shopware\PaymentMethod;

interface PaymentMethodInterface
{
    public function getName(): string;

    public function getTechnicalName(): string;

    public function getPosition(): int;

    /**
     * @return array<string, array<string, string>>
     */
    public function getTranslations(): array;

    public function getPaymentHandler(): string;
}

