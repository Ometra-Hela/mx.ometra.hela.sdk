<?php

namespace Ometra\HelaSdk\Dtos;

final class ReportDto extends DataTransferObject
{
    /**
     * @param array<string, mixed> $attributes
     * @param array<string, mixed> $summary
     * @param list<array<string, mixed>> $series
     * @param list<array<string, mixed>> $breakdown
     */
    public function __construct(
        array $attributes = [],
        public readonly ?string $type = null,
        public readonly ?string $from = null,
        public readonly ?string $to = null,
        public readonly ?string $groupBy = null,
        public readonly ?string $generatedAt = null,
        public readonly array $summary = [],
        public readonly array $series = [],
        public readonly array $breakdown = [],
    ) {
        parent::__construct($attributes);
    }

    public static function from(mixed $payload): static
    {
        $data = self::normalize($payload);

        return new self(
            attributes: $data,
            type: self::nullableString($data['type'] ?? null),
            from: self::nullableString($data['from'] ?? null),
            to: self::nullableString($data['to'] ?? null),
            groupBy: self::nullableString(self::firstValue($data, ['groupBy', 'group_by'])),
            generatedAt: self::nullableString(self::firstValue($data, ['generatedAt', 'generated_at'])),
            summary: self::arrayValue($data['summary'] ?? null),
            series: self::arrayValue($data['series'] ?? null),
            breakdown: self::arrayValue($data['breakdown'] ?? null),
        );
    }

    /** @return array<mixed> */
    private static function arrayValue(mixed $value): array
    {
        return is_array($value) ? $value : [];
    }
}
