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
        public readonly int|string|null $supplementaryId = null,
        public readonly ?string $altanName = null,
        public readonly ?string $publicName = null,
        public readonly ?float $publicPrice = null,
        public readonly ?float $data = null,
        public readonly int|float|null $validity = null,
        public readonly ?string $validityUnits = null,
        public readonly ?int $expiration = null,
        public readonly ?string $expirationUnits = null,
        public readonly ?string $product = null,
        public readonly ?string $serviceType = null,
        public readonly int|string|null $minutes = null,
        public readonly int|string|null $sms = null,
        public readonly ?float $altanPrice = null,
        public readonly int|string|bool|null $status = null,
    ) {
        parent::__construct($attributes);
    }

    public static function from(mixed $payload): static
    {
        $data = self::normalize($payload);

        return new self(
            attributes: $data,
            id: self::firstValue($data, ['id', 'offer_id', 'id_offer']),
            supplementaryId: self::firstValue($data, ['supplementaryId', 'supplementary_id']),
            altanName: self::nullableString(self::firstValue($data, ['altanName', 'altan_name'])),
            publicName: self::nullableString(self::firstValue($data, ['publicName', 'public_name', 'name'])),
            publicPrice: self::nullableFloat(self::firstValue($data, ['publicPrice', 'public_price', 'price'])),
            data: self::nullableFloat($data['data'] ?? null),
            validity: self::nullableNumber(self::firstValue($data, ['validity'])),
            validityUnits: self::nullableString(self::firstValue($data, ['validityUnits', 'validity_units'])),
            expiration: isset($data['expiration']) ? (int) $data['expiration'] : null,
            expirationUnits: self::nullableString(self::firstValue($data, ['expirationUnits', 'expiration_units'])),
            product: self::nullableString($data['product'] ?? null),
            serviceType: self::nullableString(self::firstValue($data, ['serviceType', 'service_type'])),
            minutes: self::firstValue($data, ['minutes']),
            sms: self::firstValue($data, ['sms']),
            altanPrice: self::nullableFloat(self::firstValue($data, ['altanPrice', 'altan_price'])),
            status: self::firstValue($data, ['status']),
        );
    }

    private static function nullableNumber(mixed $value): int|float|null
    {
        if ($value === null || $value === '') {
            return null;
        }

        $number = (float) $value;

        return floor($number) === $number ? (int) $number : $number;
    }
}
