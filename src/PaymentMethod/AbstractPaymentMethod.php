<?php

declare(strict_types=1);

/**
 * Copyright (c) 2024 Coincharge
 * This file is open source and available under the MIT license.
 * See the LICENSE file for more info.
 *
 * Author: Coincharge<shopware@coincharge.io>
 */

namespace Coincharge\Shopware\PaymentMethod;

abstract class AbstractPaymentMethod implements PaymentMethodInterface
{
    private const FALLBACK_LOCALE = '2fbb5fe2e29a4d70aa5854ce7ce3e20b';

    /**
     * @param array<string, array{name: string, description: string}> $translations
     */
    public function __construct(
        private readonly string $name,
        private readonly string $technicalName,
        private readonly int $position,
        private readonly string $handlerClass,
        private readonly array $translations,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getTechnicalName(): string
    {
        return $this->technicalName;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    /**
     * @return array<string, array{name: string, description: string}>
     */
    public function getTranslations(): array
    {
        $translations = $this->translations;

        if (!isset($translations[self::FALLBACK_LOCALE])) {
            $translations[self::FALLBACK_LOCALE] = $translations['en-GB'] ?? [
                'name' => $this->name,
                'description' => $this->name,
            ];
        }

        return $translations;
    }

    public function getPaymentHandler(): string
    {
        return $this->handlerClass;
    }
}
