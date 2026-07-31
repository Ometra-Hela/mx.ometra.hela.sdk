<?php

namespace Ometra\HelaSdk\Dtos;

final class DashboardDto extends DataTransferObject
{
    /**
     * @param array<string, mixed> $attributes
     * @param array<string, mixed> $kpis
     * @param array<string, mixed> $spending
     * @param array<string, mixed> $services
     * @param array<string, mixed> $inventory
     * @param array<string, mixed> $billing
     * @param list<array<string, mixed>> $alerts
     * @param list<array<string, mixed>> $recentActivity
     */
    public function __construct(
        array $attributes = [],
        public readonly ?string $period = null,
        public readonly ?string $generatedAt = null,
        public readonly array $kpis = [],
        public readonly array $spending = [],
        public readonly array $services = [],
        public readonly array $inventory = [],
        public readonly array $billing = [],
        public readonly array $alerts = [],
        public readonly array $recentActivity = [],
    ) {
        parent::__construct($attributes);
    }

    public static function from(mixed $payload): static
    {
        $data = self::normalize($payload);

        return new self(
            attributes: $data,
            period: self::nullableString($data['period'] ?? null),
            generatedAt: self::nullableString(self::firstValue($data, ['generatedAt', 'generated_at'])),
            kpis: self::arrayValue($data['kpis'] ?? null),
            spending: self::arrayValue($data['spending'] ?? null),
            services: self::arrayValue($data['services'] ?? null),
            inventory: self::arrayValue($data['inventory'] ?? null),
            billing: self::arrayValue($data['billing'] ?? null),
            alerts: self::arrayValue($data['alerts'] ?? null),
            recentActivity: self::arrayValue(self::firstValue($data, ['recentActivity', 'recent_activity'], [])),
        );
    }

    /** @return array<mixed> */
    private static function arrayValue(mixed $value): array
    {
        return is_array($value) ? $value : [];
    }
}
