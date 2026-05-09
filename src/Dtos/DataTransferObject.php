<?php

namespace Ometra\HelaSdk\Dtos;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

abstract class DataTransferObject implements Arrayable, JsonSerializable
{
    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(public readonly array $attributes = [])
    {
    }

    public function __get(string $key): mixed
    {
        return $this->attributes[$key] ?? null;
    }

    public function __isset(string $key): bool
    {
        return array_key_exists($key, $this->attributes);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }

    public static function from(mixed $payload): static
    {
        return new static(static::normalize($payload));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->attributes;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * @return array<string, mixed>
     */
    public static function normalize(mixed $value): array
    {
        if ($value instanceof self) {
            return $value->toArray();
        }

        if (is_array($value)) {
            return $value;
        }

        if (is_object($value)) {
            return json_decode(json_encode($value), true) ?: [];
        }

        return ['value' => $value];
    }

    /**
     * @param array<string, mixed> $data
     * @param array<int, string> $keys
     */
    protected static function firstValue(array $data, array $keys, mixed $default = null): mixed
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $data)) {
                return $data[$key];
            }
        }

        return $default;
    }

    protected static function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return (string) $value;
    }

    protected static function nullableFloat(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (float) $value;
    }
}
