<?php

namespace Ometra\HelaSdk\Dtos;

final class OrderDto extends DataTransferObject
{
    public readonly DtoCollection $items;

    public readonly DtoCollection $payments;

    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(
        array $attributes = [],
        public readonly int|string|null $id = null,
        public readonly int|string|null $clientId = null,
        public readonly ?string $email = null,
        public readonly ?float $total = null,
        public readonly ?bool $published = null,
        public readonly ?bool $processed = null,
    ) {
        $this->items = DtoCollection::from($attributes['items'] ?? [], OrderItemDto::class);
        $this->payments = DtoCollection::from($attributes['payments'] ?? [], PaymentDto::class);

        parent::__construct($attributes);
    }

    public static function from(mixed $payload): static
    {
        $data = self::normalize($payload);
        if (isset($data['order']) && is_array($data['order'])) {
            $data = $data['order'];
        }

        return new self(
            attributes: $data,
            id: self::firstValue($data, ['id_order', 'order_id', 'id']),
            clientId: self::firstValue($data, ['id_client', 'client_id']),
            email: self::nullableString($data['email'] ?? null),
            total: self::nullableFloat(self::firstValue($data, ['order_total', 'total'])),
            published: isset($data['published']) ? (bool) $data['published'] : null,
            processed: isset($data['processed']) ? (bool) $data['processed'] : null,
        );
    }
}
