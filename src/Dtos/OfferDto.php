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
        public readonly ?float $listPrice = null,
        public readonly ?float $effectivePrice = null,
        public readonly ?bool $hasClientPrice = null,
        public readonly ?bool $purchasable = null,
        /** @var array{activation?: bool, renewal?: bool, topup?: bool, purchase?: bool} */
        public readonly array $capabilities = [],
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
        public readonly ?bool $allowsNewLineActivation = null,
        public readonly ?bool $commissionEnabled = null,
        public readonly ?float $commissionActivationAmount = null,
        public readonly ?float $commissionRenewalAmount = null,
        public readonly ?float $commissionPortabilityRate = null,
        public readonly ?float $commissionRetentionRate = null,
        public readonly ?int $commissionRetentionMonths = null,
        public readonly ?bool $commissionRetentionEnabled = null,
        public readonly ?string $commissionNotes = null,
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
            publicPrice: self::nullableFloat(self::firstValue($data, ['publicPrice', 'public_price', 'price', 'effective_price', 'list_price'])),
            listPrice: self::nullableFloat(self::firstValue($data, ['listPrice', 'list_price', 'publicPrice', 'public_price', 'price'])),
            effectivePrice: self::nullableFloat(self::firstValue($data, ['effectivePrice', 'effective_price', 'publicPrice', 'public_price', 'price'])),
            hasClientPrice: self::nullableBool(self::firstValue($data, ['hasClientPrice', 'has_client_price'], false)),
            purchasable: self::nullableBool(self::firstValue($data, ['purchasable'], true)),
            capabilities: self::capabilities($data['capabilities'] ?? []),
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
            allowsNewLineActivation: self::nullableBool(self::firstValue($data, ['allowsNewLineActivation', 'allows_new_line_activation'])),
            commissionEnabled: self::nullableBool(self::firstValue($data, ['commissionEnabled', 'commission_enabled'])),
            commissionActivationAmount: self::nullableFloat(self::firstValue($data, ['commissionActivationAmount', 'commission_activation_amount'])),
            commissionRenewalAmount: self::nullableFloat(self::firstValue($data, ['commissionRenewalAmount', 'commission_renewal_amount'])),
            commissionPortabilityRate: self::nullableFloat(self::firstValue($data, ['commissionPortabilityRate', 'commission_portability_rate'])),
            commissionRetentionRate: self::nullableFloat(self::firstValue($data, ['commissionRetentionRate', 'commission_retention_rate'])),
            commissionRetentionMonths: self::nullableInt(self::firstValue($data, ['commissionRetentionMonths', 'commission_retention_months'])),
            commissionRetentionEnabled: self::nullableBool(self::firstValue($data, ['commissionRetentionEnabled', 'commission_retention_enabled'])),
            commissionNotes: self::nullableString(self::firstValue($data, ['commissionNotes', 'commission_notes'])),
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

    private static function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (int) $value : null;
    }

    private static function nullableBool(mixed $value): ?bool
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int) $value === 1;
        }

        $normalized = strtolower(trim((string) $value));

        return match ($normalized) {
            '1', 'true', 'yes', 'on' => true,
            '0', 'false', 'no', 'off' => false,
            default => null,
        };
    }

    /**
     * @return array{activation?: bool, renewal?: bool, topup?: bool, purchase?: bool}
     */
    private static function capabilities(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $capabilities = [];
        foreach (['activation', 'renewal', 'topup', 'purchase'] as $capability) {
            if (array_key_exists($capability, $value)) {
                $capabilities[$capability] = self::nullableBool($value[$capability]) ?? false;
            }
        }

        return $capabilities;
    }
}
