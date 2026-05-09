<?php

namespace Ometra\HelaSdk\Dtos;

final class ShippingQuoteDto extends DataTransferObject
{
    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(
        array $attributes = [],
        public readonly int|string|null $providerId = null,
        public readonly ?string $providerName = null,
        public readonly ?string $serviceName = null,
        public readonly ?float $price = null,
    ) {
        parent::__construct($attributes);
    }

    public static function from(mixed $payload): static
    {
        $data = self::normalize($payload);

        return new self(
            attributes: $data,
            providerId: self::firstValue($data, ['provider_id', 'id_provider', 'id']),
            providerName: self::nullableString($data['provider_name'] ?? null),
            serviceName: self::nullableString($data['service_name'] ?? null),
            price: self::nullableFloat(self::firstValue($data, ['price', 'shipping_cost', 'cost'])),
        );
    }
}
