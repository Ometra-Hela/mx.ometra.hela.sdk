<?php

namespace Ometra\HelaSdk\Dtos;

final class NotificationPreferencesDto extends DataTransferObject
{
    public static function from(mixed $payload): static
    {
        return new self(self::normalize($payload));
    }

    /** @return list<array<string, mixed>> */
    public function catalog(): array
    {
        $catalog = $this->attributes['catalog'] ?? [];

        return is_array($catalog) ? array_values($catalog) : [];
    }

    /** @return list<array<string, mixed>> */
    public function preferences(): array
    {
        $preferences = $this->attributes['preferences'] ?? [];

        return is_array($preferences) ? array_values($preferences) : [];
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return $this->attributes;
    }
}
