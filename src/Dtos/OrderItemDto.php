<?php

namespace Ometra\HelaSdk\Dtos;

final class OrderItemDto extends DataTransferObject
{
    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(
        array $attributes = [],
        public readonly int|string|null $id = null,
        public readonly ?string $type = null,
        public readonly ?string $key = null,
        public readonly ?string $description = null,
        public readonly ?float $price = null,
        public readonly ?float $finalPrice = null,
        public readonly ?string $target = null,
    ) {
        parent::__construct($attributes);
    }

    public static function from(mixed $payload): static
    {
        $data = self::normalize($payload);

        return new self(
            attributes: $data,
            id: self::firstValue($data, ['id', 'idOrderItem', 'id_orderItem', 'item_id']),
            type: self::nullableString(self::firstValue($data, ['type', 'item_type'])),
            key: self::nullableString($data['key'] ?? null),
            description: self::nullableString($data['description'] ?? null),
            price: self::nullableFloat($data['price'] ?? null),
            finalPrice: self::nullableFloat(self::firstValue($data, ['finalPrice', 'final_price', 'price'])),
            target: self::nullableString($data['target'] ?? null),
        );
    }
}
