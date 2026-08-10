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
            if (! $item instanceof DataTransferObject) {
                throw new \InvalidArgumentException(
                    'DtoCollection only accepts DataTransferObject instances; got '.get_debug_type($item)
                );
            }

            /** @var T $item */
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

    /**
     * Count the number of DTOs in the collection.
     *
     * @return int The number of items
     */
    #[\Override]
    public function count(): int
    {
        return count($this->items);
    }

    /**
     * @return Traversable<int, T>
     */
    #[\Override]
    public function getIterator(): Traversable
    {
        foreach ($this->items as $key => $item) {
            yield $key => $item;
        }
    }

    #[\Override]
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->items[$offset]);
    }

    #[\Override]
    public function offsetGet(mixed $offset): mixed
    {
        return $this->items[$offset] ?? null;
    }

    /**
     * @param  int|null  $offset
     * @param  T  $value
     *
     * @throws \InvalidArgumentException If value is not a DataTransferObject instance
     */
    #[\Override]
    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (! $value instanceof DataTransferObject) {
            throw new \InvalidArgumentException(
                'DtoCollection only accepts DataTransferObject instances; got '.get_debug_type($value)
            );
        }

        if ($offset === null) {
            $this->items[] = $value;
        } else {
            $this->items[$offset] = $value;
        }
    }

    #[\Override]
    public function offsetUnset(mixed $offset): void
    {
        unset($this->items[$offset]);
        // Re-index to prevent gaps that break last(), map(), and count() consistency
        $this->items = array_values($this->items);
    }

    #[\Override]
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
     * Push a DTO onto the end of the collection (mutates in-place).
     *
     * Unlike {@see append()} which returns a new immutable collection,
     * push() modifies the current instance and returns it for chaining.
     *
     * @param  T  $dto
     *
     * @return self
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
     * Uses end() instead of count()-1 to be resilient against
     * any future index gaps.
     *
     * @return T|null
     */
    public function last(): ?DataTransferObject
    {
        if ($this->items === []) {
            return null;
        }

        // end() moves the internal pointer; use a copy to avoid side effects
        $copy = $this->items;

        /** @var T|false $last */
        $last = end($copy);

        return $last === false ? null : $last;
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
     * Pluck a single property value from each DTO in the collection.
     *
     * Useful for extracting a list of IDs, emails, or any single field
     * from a collection of DTOs without manual mapping.
     *
     * Uses reflection to safely access public readonly properties,
     * avoiding dynamic property access that triggers PHPStan warnings.
     *
     * @return array<int, mixed>
     */
    public function pluck(string $key): array
    {
        return array_map(
            static function (DataTransferObject $dto) use ($key): mixed {
                $ref = new \ReflectionProperty($dto, $key);

                return $ref->getValue($dto);
            },
            $this->items
        );
    }

    /**
     * Get a single column as key/value pairs from the collection.
     *
     * Uses one property as keys and another as values. If no value
     * key is given, the entire DTO array is used as the value.
     *
     * Uses reflection to safely access public readonly properties,
     * avoiding dynamic property access that triggers PHPStan warnings.
     *
     * @return array<int|string, mixed>
     */
    public function pluckKey(string $keyField, ?string $valueField = null): array
    {
        $result = [];

        foreach ($this->items as $item) {
            /** @var int|string $keyValue */
            $keyRef = new \ReflectionProperty($item, $keyField);
            $keyValue = $keyRef->getValue($item);
            $result[$keyValue] = $valueField !== null
                ? (new \ReflectionProperty($item, $valueField))->getValue($item)
                : $item->toArray();
        }

        return $result;
    }

    /**
     * Append a DTO to the collection and return a new collection.
     *
     * Unlike {@see push()} which mutates in place, this returns a new
     * DtoCollection instance, enabling fluent/immutable chaining.
     *
     * @param  DataTransferObject  $dto
     * @return self
     *
     * @phpstan-return DtoCollection<DataTransferObject>
     */
    public function append(DataTransferObject $dto): self
    {
        $clone = clone $this;
        $clone->items[] = $dto;

        return $clone;
    }

    /**
     * Merge another DtoCollection's items into a new collection.
     *
     * Returns a new collection containing all items from both collections.
     * Neither original collection is mutated.
     *
     * @param  self  $other  The collection to merge
     * @return self
     *
     * @phpstan-return DtoCollection<DataTransferObject>
     */
    public function merge(self $other): self
    {
        return new self([...$this->items, ...$other->items]);
    }

    /**
     * Check if the collection is empty.
     *
     * @return bool True when the collection contains no items
     */
    public function isEmpty(): bool
    {
        return $this->items === [];
    }

    /**
     * Check if the collection is not empty.
     *
     * @return bool True when the collection contains at least one item
     */
    public function isNotEmpty(): bool
    {
        return $this->items !== [];
    }
}
