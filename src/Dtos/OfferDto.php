<?php

namespace Ometra\HelaSdk\Dtos;

final class OfferDto extends DataTransferObject
{
    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(
        array $attributes = [],
        public readonly int|string|null $id = null,
        public readonly ?string $publicName = null,
        public readonly ?float $publicPrice = null,
        public readonly ?float $data = null,
        public readonly ?int $expiration = null,
        public readonly ?string $expirationUnits = null,
        public readonly ?string $product = null,
        public readonly ?string $serviceType = null,
    ) {
        parent::__construct($attributes);
    }

    public static function from(mixed $payload): static
    {
        $data = self::normalize($payload);

        return new self(
            attributes: $data,
            id: self::firstValue($data, ['offer_id', 'id_offer', 'id']),
            publicName: self::nullableString(self::firstValue($data, ['public_name', 'name'])),
            publicPrice: self::nullableFloat(self::firstValue($data, ['public_price', 'price'])),
            data: self::nullableFloat($data['data'] ?? null),
            expiration: isset($data['expiration']) ? (int) $data['expiration'] : null,
            expirationUnits: self::nullableString($data['expiration_units'] ?? null),
            product: self::nullableString($data['product'] ?? null),
            serviceType: self::nullableString($data['service_type'] ?? null),
        );
    }
}
