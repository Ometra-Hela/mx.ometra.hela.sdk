<?php

namespace Ometra\HelaSdk\Dtos;

final class PaymentDto extends DataTransferObject
{
    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(
        array $attributes = [],
        public readonly int|string|null $id = null,
        public readonly ?string $method = null,
        public readonly ?string $status = null,
        public readonly ?float $amount = null,
    ) {
        parent::__construct($attributes);
    }

    public static function from(mixed $payload): static
    {
        $data = self::normalize($payload);

        return new self(
            attributes: $data,
            id: self::firstValue($data, ['id', 'id_payment', 'payment_id']),
            method: self::nullableString(self::firstValue($data, ['method', 'payment_method'])),
            status: self::nullableString($data['status'] ?? null),
            amount: self::nullableFloat($data['amount'] ?? null),
        );
    }
}
