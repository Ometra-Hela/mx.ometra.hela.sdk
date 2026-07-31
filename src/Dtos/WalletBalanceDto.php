<?php

namespace Ometra\HelaSdk\Dtos;

final class WalletBalanceDto extends DataTransferObject
{
    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(
        array $attributes = [],
        public readonly float $availableBalance = 0.0,
        public readonly float $pendingBalance = 0.0,
        public readonly string $currency = 'MXN',
        public readonly string $status = 'active',
    ) {
        parent::__construct($attributes);
    }

    public static function from(mixed $payload): static
    {
        $data = self::normalize($payload);

        return new self(
            attributes: $data,
            availableBalance: (float) self::firstValue(
                $data,
                ['available_balance', 'availableBalance', 'balance'],
                0,
            ),
            pendingBalance: (float) self::firstValue(
                $data,
                ['pending_balance', 'pendingBalance'],
                0,
            ),
            currency: self::nullableString($data['currency'] ?? null) ?? 'MXN',
            status: self::nullableString($data['status'] ?? null) ?? 'active',
        );
    }
}
