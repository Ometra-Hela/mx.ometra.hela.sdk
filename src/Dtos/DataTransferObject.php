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
        if (array_key_exists($key, $this->attributes)) {
            return $this->attributes[$key];
        }

        $property = self::camelKey($key);

        return property_exists($this, $property) ? $this->{$property} : null;
    }

    public function __isset(string $key): bool
    {
        return array_key_exists($key, $this->attributes)
            || property_exists($this, self::camelKey($key));
    }

    public function get(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $this->attributes)) {
            return $this->attributes[$key];
        }

        $property = self::camelKey($key);

        return property_exists($this, $property) ? $this->{$property} : $default;
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
        $data = $this->canonicalizeArray($this->attributes);
        foreach (get_object_vars($this) as $key => $value) {
            if ($key === 'attributes') {
                continue;
            }

            $data[$key] = $this->serializeValue($value);
        }

        return $data === [] ? $this->attributes : $data;
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
            return $value->attributes !== [] ? $value->attributes : $value->toArray();
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

    private static function camelKey(string $key): string
    {
        if (! str_contains($key, '_')) {
            return $key;
        }

        return lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $key))));
    }

    private function serializeValue(mixed $value): mixed
    {
        if ($value instanceof Arrayable) {
            return $value->toArray();
        }

        if ($value instanceof JsonSerializable) {
            return $value->jsonSerialize();
        }

        if (is_array($value)) {
            return array_map(fn (mixed $item) => $this->serializeValue($item), $value);
        }

        if (is_object($value)) {
            return json_decode(json_encode($value), true) ?: [];
        }

        return $value;
    }

    /**
     * @param array<mixed> $value
     * @return array<mixed>
     */
    private function canonicalizeArray(array $value): array
    {
        if (array_is_list($value)) {
            return array_map(fn (mixed $item) => $this->canonicalizeValue($item), $value);
        }

        $canonical = [];
        foreach ($value as $key => $item) {
            $canonicalKey = is_string($key) ? self::camelKey($key) : $key;
            $canonical[$canonicalKey] = $this->canonicalizeValue($item);
        }

        return $canonical;
    }

    private function canonicalizeValue(mixed $value): mixed
    {
        if (is_array($value)) {
            return $this->canonicalizeArray($value);
        }

        if (is_object($value)) {
            return $this->canonicalizeArray(self::normalize($value));
        }

        return $value;
    }
}
