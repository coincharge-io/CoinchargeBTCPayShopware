<?php declare(strict_types=1);


namespace Coincharge\Shopware\Client;

interface BTCPayServerClientInterface
{
    public function sendPostRequest(string $resourceUri, array $data, array $headers = []): array;

    public function sendGetRequest(string $resourceUri, array $headers = []): array;

    public function createAuthHeader(): string;

}