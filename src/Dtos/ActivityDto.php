<?php

namespace Ometra\HelaSdk\Dtos;

final class ActivityDto extends DataTransferObject
{
    public function __construct(array $attributes = [], public readonly int|string|null $id = null, public readonly ?string $action = null, public readonly ?string $notes = null, public readonly ?string $date = null, public readonly ?string $executor = null)
    {
        parent::__construct($attributes);
    }

    public static function from(mixed $payload): static
    {
        $data = self::normalize($payload);
        return new self($data, $data['id'] ?? null, self::nullableString($data['action'] ?? null), self::nullableString($data['notes'] ?? null), self::nullableString($data['date'] ?? null), self::nullableString($data['executor'] ?? null));
    }
}
