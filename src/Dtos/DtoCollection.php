<?php

namespace Ometra\HelaSdk\Dtos;

use Countable;
use Illuminate\Contracts\Support\Arrayable;
use IteratorAggregate;
use JsonSerializable;
use Traversable;

/**
 * @template T of DataTransferObject
 * @implements IteratorAggregate<int, T>
 */
final class DtoCollection implements Arrayable, Countable, IteratorAggregate, JsonSerializable
{
    /**
     * @param array<int, T> $items
     * @param array<string, mixed> $meta
     * @param array<string, mixed> $attributes
     */
    public function __construct(
        public readonly array $items,
        public readonly array $meta = [],
        public readonly array $attributes = [],
    ) {
    }

    /**
     * @template TDto of DataTransferObject
     * @param class-string<TDto> $dtoClass
     * @return self<TDto>
     */
    public static function from(mixed $payload, string $dtoClass): self
    {
        $attributes = DataTransferObject::normalize($payload);
        $itemsPayload = self::itemsPayload($attributes);

        $items = [];
        foreach ($itemsPayload as $item) {
            $items[] = $dtoClass::from($item);
        }

        return new self($items, self::metaPayload($attributes), $attributes);
    }

    public function first(): ?DataTransferObject
    {
        return $this->items[0] ?? null;
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function getIterator(): Traversable
    {
        yield from $this->items;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function toArray(): array
    {
        return array_map(
            fn (DataTransferObject $item) => $item->toArray(),
            $this->items,
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * @param array<string, mixed> $attributes
     * @return array<int, mixed>
     */
    private static function itemsPayload(array $attributes): array
    {
        if (array_is_list($attributes)) {
            return $attributes;
        }

        if (isset($attributes['paginator']) && is_array($attributes['paginator'])) {
            $paginator = $attributes['paginator'];
            if (isset($paginator['data']) && is_array($paginator['data'])) {
                return $paginator['data'];
            }
        }

        if (isset($attributes['data']) && is_array($attributes['data'])) {
            return array_is_list($attributes['data'])
                ? $attributes['data']
                : self::itemsPayload($attributes['data']);
        }

        if (isset($attributes['items']) && is_array($attributes['items'])) {
            return $attributes['items'];
        }

        foreach (['orders', 'services', 'users', 'invoices', 'portabilities', 'sim_cards', 'offers'] as $key) {
            if (isset($attributes[$key]) && is_array($attributes[$key])) {
                return $attributes[$key];
            }
        }

        return [];
    }

    /**
     * @param array<string, mixed> $attributes
     * @return array<string, mixed>
     */
    private static function metaPayload(array $attributes): array
    {
        if (isset($attributes['paginator']) && is_array($attributes['paginator'])) {
            $meta = $attributes['paginator'];
            unset($meta['data']);

            return $meta;
        }

        if (isset($attributes['data']) && is_array($attributes['data']) && array_is_list($attributes['data'])) {
            $meta = $attributes;
            unset($meta['data']);

            return $meta;
        }

        if (isset($attributes['data']) && is_array($attributes['data']) && ! array_is_list($attributes['data'])) {
            return self::metaPayload($attributes['data']);
        }

        return [];
    }
}
