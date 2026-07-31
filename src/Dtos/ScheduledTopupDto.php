<?php

namespace Ometra\HelaSdk\Dtos;

final class ScheduledTopupDto extends DataTransferObject
{
    public function __construct(array $attributes = [], public readonly int|string|null $id = null, public readonly ?string $targetType = null, public readonly int|string|null $targetId = null, public readonly ?string $targetName = null, public readonly ?string $offerId = null, public readonly ?string $paymentMethod = null, public readonly ?string $scheduleType = null, public readonly ?string $frequency = null, public readonly ?string $timezone = null, public readonly ?string $startsAt = null, public readonly ?string $endsAt = null, public readonly ?string $nextRunAt = null, public readonly ?string $status = null, public readonly array $runs = [])
    {
        parent::__construct($attributes);
    }

    public static function from(mixed $payload): static
    {
        $data = self::normalize($payload);
        return new self($data, $data['id'] ?? null, self::nullableString($data['target_type'] ?? null), $data['target_id'] ?? null, self::nullableString($data['target_name'] ?? null), self::nullableString($data['offer_id'] ?? null), self::nullableString($data['payment_method'] ?? null), self::nullableString($data['schedule_type'] ?? null), self::nullableString($data['frequency'] ?? null), self::nullableString($data['timezone'] ?? null), self::nullableString($data['starts_at'] ?? null), self::nullableString($data['ends_at'] ?? null), self::nullableString($data['next_run_at'] ?? null), self::nullableString($data['status'] ?? null), is_array($data['runs'] ?? null) ? $data['runs'] : []);
    }
}
