<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO;

use ArrayAccess;
use Countable;
use IteratorAggregate;
use JsonSerializable;
use Traversable;

/**
 * Type-safe collection of DTO instances.
 *
 * Wraps an array of DTOs and provides type-safe access,
 * JSON serialization, and array-like convenience methods.
 *
 * @template T of DataTransferObject
 *
 * @implements IteratorAggregate<int, T>
 * @implements ArrayAccess<int, T>
 */
final class DtoCollection implements ArrayAccess, Countable, IteratorAggregate, JsonSerializable
{
    /** @var array<int, T> */
    private array $items = [];

    /**
     * @param  array<int, DataTransferObject>  $items
     */
    public function __construct(array $items = [])
    {
        foreach ($items as $item) {
            $this->items[] = $item;
        }
    }

    /**
     * Get all items as a plain array (each DTO serialized via toArray()).
     *
     * @return array<int, array<string, mixed>>
     */
    public function toArray(): array
    {
        return array_map(
            static fn (DataTransferObject $dto): array => $dto->toArray(),
            $this->items
        );
    }

    /**
     * Get all items as a plain array including hidden properties.
     *
     * @return array<int, array<string, mixed>>
     */
    public function allValues(): array
    {
        return array_map(
            static fn (DataTransferObject $dto): array => $dto->allValues(),
            $this->items
        );
    }

    /**
     * Get the raw DTO instances.
     *
     * @return array<int, T>
     */
    public function items(): array
    {
        return $this->items;
    }

    public function count(): int
    {
        return count($this->items);
    }

    /**
     * @return Traversable<int, T>
     */
    public function getIterator(): Traversable
    {
        foreach ($this->items as $key => $item) {
            yield $key => $item;
        }
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->items[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->items[$offset] ?? null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($offset === null) {
            $this->items[] = $value;
        } else {
            $this->items[$offset] = $value;
        }
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->items[$offset]);
    }

    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }

    /**
     * Create a new collection with the given items.
     *
     * @param  array<int, DataTransferObject>  $items
     *
     * @phpstan-return DtoCollection<DataTransferObject>
     */
    public static function make(array $items = []): self
    {
        return new self($items);
    }

    /**
     * Push a DTO onto the end of the collection.
     *
     * @param  T  $dto
     *
     * @phpstan-return DtoCollection<DataTransferObject>
     */
    public function push(DataTransferObject $dto): self
    {
        $this->items[] = $dto;

        return $this;
    }

    /**
     * Get the first item or null if empty.
     *
     * @return T|null
     */
    public function first(): ?DataTransferObject
    {
        return $this->items[0] ?? null;
    }

    /**
     * Get the last item or null if empty.
     *
     * @return T|null
     */
    public function last(): ?DataTransferObject
    {
        if ($this->items === []) {
            return null;
        }

        return $this->items[count($this->items) - 1];
    }

    /**
     * Map over each DTO and return a plain array of results.
     *
     * @template R
     *
     * @param  callable(T, int): R  $callback
     * @return array<int, R>
     */
    public function map(callable $callback): array
    {
        return array_map($callback, $this->items, array_keys($this->items));
    }

    /**
     * Filter items by a callback and return a new collection.
     *
     * @param  callable(T): bool  $callback
     *
     * @phpstan-return DtoCollection<DataTransferObject>
     */
    public function filter(callable $callback): self
    {
        return new self(array_values(array_filter($this->items, $callback)));
    }

    /**
     * Check if the collection is empty.
     */
    public function isEmpty(): bool
    {
        return $this->items === [];
    }

    /**
     * Check if the collection is not empty.
     */
    public function isNotEmpty(): bool
    {
        return $this->items !== [];
    }
}
