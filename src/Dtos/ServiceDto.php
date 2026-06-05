<?php

namespace Ometra\HelaSdk\Dtos;

final class ServiceDto extends DataTransferObject
{
    /**
     * @param array<string, mixed> $attributes
     * @param array<int, mixed> $users
     * @param array<string, mixed> $linking
     * @param array<string, mixed> $consumptionSummary
     */
    public function __construct(
        array $attributes = [],
        public readonly int|string|null $id = null,
        public readonly int|string|null $clientId = null,
        public readonly int|string|null $offerId = null,
        public readonly int|string|null $groupId = null,
        public readonly ?string $msisdn = null,
        public readonly ?string $name = null,
        public readonly ?string $clientName = null,
        public readonly ?string $status = null,
        public readonly ?string $statusLabel = null,
        public readonly ?string $statusVariant = null,
        public readonly ?string $altanStatus = null,
        public readonly ?string $serviceType = null,
        public readonly ?string $serviceTypeCode = null,
        public readonly ?string $groupName = null,
        public readonly ?string $groupIcon = null,
        public readonly ?string $offerName = null,
        public readonly ?string $product = null,
        public readonly ?bool $isLinked = null,
        public readonly ?bool $requiresLinking = null,
        public readonly array $linking = [],
        public readonly ?string $registrationDate = null,
        public readonly ?string $lastTopupDate = null,
        public readonly ?string $lastTopupExpiry = null,
        public readonly ?string $expiryDate = null,
        public readonly ?string $dtExpiry = null,
        public readonly ?int $expiryDays = null,
        public readonly ?bool $isNearExpiry = null,
        public readonly array $consumptionSummary = [],
        public readonly ?string $dtServiceExpirity = null,
        public readonly array $users = [],
    ) {
        parent::__construct($attributes);
    }

    public static function from(mixed $payload): static
    {
        $data = self::normalize($payload);
        if (isset($data['service']) && is_array($data['service'])) {
            $data = $data['service'];
        }

        return new self(
            attributes: $data,
            id: self::firstValue($data, ['id', 'id_service', 'service_id']),
            clientId: self::firstValue($data, ['clientId', 'id_client', 'client_id']),
            offerId: self::firstValue($data, ['offerId', 'offer_id']),
            groupId: self::firstValue($data, ['groupId', 'group_id', 'id_serviceGroup']),
            msisdn: self::nullableString($data['msisdn'] ?? null),
            name: self::nullableString($data['name'] ?? null),
            clientName: self::nullableString(self::firstValue($data, ['clientName', 'client_name'])),
            status: self::nullableString($data['status'] ?? null),
            statusLabel: self::nullableString(self::firstValue($data, ['statusLabel', 'status_label'])),
            statusVariant: self::nullableString(self::firstValue($data, ['statusVariant', 'status_variant'])),
            altanStatus: self::nullableString(self::firstValue($data, ['altanStatus', 'altan_status'])),
            serviceType: self::nullableString(self::firstValue($data, ['serviceType', 'service_type'])),
            serviceTypeCode: self::nullableString(self::firstValue($data, ['serviceTypeCode', 'service_type_code'])),
            groupName: self::nullableString(self::firstValue($data, ['groupName', 'group_name'])),
            groupIcon: self::nullableString(self::firstValue($data, ['groupIcon', 'group_icon'])),
            offerName: self::nullableString(self::firstValue($data, ['offerName', 'offer_name', 'offering'])),
            product: self::nullableString($data['product'] ?? null),
            isLinked: self::nullableBool(self::firstValue($data, ['isLinked', 'is_linked'])),
            requiresLinking: self::nullableBool(self::firstValue($data, ['requiresLinking', 'requires_linking'])),
            linking: is_array($data['linking'] ?? null) ? self::canonicalizeArray($data['linking']) : [],
            registrationDate: self::nullableString(self::firstValue($data, ['registrationDate', 'registration_date'])),
            lastTopupDate: self::nullableString(self::firstValue($data, ['lastTopupDate', 'last_topup_date'])),
            lastTopupExpiry: self::nullableString(self::firstValue($data, ['lastTopupExpiry', 'last_topup_expiry'])),
            expiryDate: self::nullableString(self::firstValue($data, ['expiryDate', 'expiry_date'])),
            dtExpiry: self::nullableString(self::firstValue($data, ['dtExpiry', 'dt_expiry', 'expiry_date_raw'])),
            expiryDays: self::nullableInt(self::firstValue($data, ['expiryDays', 'expiry_days'])),
            isNearExpiry: self::nullableBool(self::firstValue($data, ['isNearExpiry', 'is_near_expiry'])),
            consumptionSummary: is_array(self::firstValue($data, ['consumptionSummary', 'consumption_summary'], []))
                ? self::canonicalizeArray(self::firstValue($data, ['consumptionSummary', 'consumption_summary'], []))
                : [],
            dtServiceExpirity: self::nullableString(self::firstValue($data, ['dtServiceExpirity', 'dt_service_expirity'])),
            users: is_array($data['users'] ?? null) ? $data['users'] : [],
        );
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
     * @param array<mixed> $value
     * @return array<mixed>
     */
    private static function canonicalizeArray(array $value): array
    {
        if (array_is_list($value)) {
            return array_map(fn (mixed $item): mixed => self::canonicalizeValue($item), $value);
        }

        $canonical = [];
        foreach ($value as $key => $item) {
            $canonicalKey = is_string($key) ? self::camelKey($key) : $key;
            $canonical[$canonicalKey] = self::canonicalizeValue($item);
        }

        return $canonical;
    }

    private static function canonicalizeValue(mixed $value): mixed
    {
        if (is_array($value)) {
            return self::canonicalizeArray($value);
        }

        if (is_object($value)) {
            return self::canonicalizeArray(self::normalize($value));
        }

        return $value;
    }

    private static function camelKey(string $key): string
    {
        if (! str_contains($key, '_')) {
            return $key;
        }

        return lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $key))));
    }
}
