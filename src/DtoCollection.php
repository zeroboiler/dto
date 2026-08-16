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
 * Implements ArrayAccess for `$collection[0]` syntax,
 * Countable for `count($collection)`, IteratorAggregate for
 * `foreach ($collection as $dto)`, and JsonSerializable for
 * `json_encode($collection)`.
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
     * Prevent cloning of the collection.
     *
     * Use {@see append()} or {@see merge()} for immutable collection operations.
     * Internal clone is allowed via {@see cloneCollection()} for append/merge.
     *
     * @throws \RuntimeException Always
     * @internal Magic method intercepted — call append() or merge() instead
     */
    public function __clone(): never
    {
        throw new \RuntimeException(
            'DtoCollection is immutable. Use append(), merge(), or filter() to create a new collection.'
        );
    }

    /**
     * Internal clone helper for immutable operations (append, merge).
     *
     * Bypasses the __clone() restriction to enable the shallow copy
     * needed by {@see append()} and other immutable methods.
     *
     * @internal Not part of the public API.
     */
    private function cloneCollection(): self
    {
        $clone = new self;
        $clone->items = $this->items;

        return $clone;
    }

    /**
     * Get debug output for var_dump/print_r.
     *
     * Shows item count and first 3 items (truncated) to avoid
     * flooding the debugger with large collections.
     *
     * @return array{count: int, items: list<array<string, mixed>>}
     */
    public function __debugInfo(): array
    {
        return [
            'count' => count($this->items),
            'items' => array_slice(
                array_map(
                    static fn (DataTransferObject $dto): array => $dto->toArray(),
                    $this->items
                ),
                0,
                3
            ),
        ];
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
     * Get an iterator for the collection items.
     *
     * Enables foreach traversal: `foreach ($collection as $dto) { ... }`
     *
     * @return Traversable<int, T>
     */
    #[\Override]
    public function getIterator(): Traversable
    {
        foreach ($this->items as $key => $item) {
            yield $key => $item;
        }
    }

    /**
     * Check if an item exists at the given offset.
     *
     * @param  mixed  $offset  The index to check (0-based)
     * @return bool True if the offset exists in the collection
     */
    #[\Override]
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->items[$offset]);
    }

    /**
     * Get the item at the given offset, or null if the offset doesn't exist.
     *
     * @param  mixed  $offset  The index to retrieve (0-based)
     * @return T|null The DTO instance at the offset, or null
     *
     * @phpstan-return T|null
     */
    #[\Override]
    public function offsetGet(mixed $offset): mixed
    {
        return $this->items[$offset] ?? null;
    }

    /**
     * Set an item at the given offset.
     *
     * @param  mixed  $offset  The index to set (0-based). null appends to the end.
     * @param  DataTransferObject  $value  The DTO instance to store
     *
     * @throws \InvalidArgumentException If value is not a DataTransferObject instance
     * @return void
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

    /**
     * Remove an item at the given offset and re-index the collection.
     *
     * After unsetting, the internal array is re-indexed with array_values()
     * to prevent gaps that would break last(), map(), and count() consistency.
     *
     * @param  mixed  $offset  The index to remove (0-based)
     * @return void
     */
    #[\Override]
    public function offsetUnset(mixed $offset): void
    {
        unset($this->items[$offset]);
        // Re-index to prevent gaps that break last(), map(), and count() consistency
        $this->items = array_values($this->items);
    }

    /**
     * Serialize the collection to a JSON-serializable value.
     *
     * Each DTO is serialized via toArray(), producing an array of associative arrays.
     * Used by json_encode($collection).
     *
     * @return array<int, array<string, mixed>>
     */
    #[\Override]
    public function jsonSerialize(): array
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
     * @param  DataTransferObject  $dto  The DTO to append
     *
     * @return self This collection instance (for chaining)
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
     * Unlike Laravel Collection's map(), this returns a plain array, not a DtoCollection.
     * The callback receives the DTO instance and its 0-based index.
     *
     * @template R
     *
     * @param  callable(T, int): R  $callback
     * @return array<int, R>
     *
     * @phpstan-return array<int, R>
     */
    public function map(callable $callback): array
    {
        return array_map($callback, $this->items, array_keys($this->items));
    }

    /**
     * Filter items by a callback and return a new collection.
     *
     * The resulting collection is re-indexed to prevent gaps,
     * consistent with {@see offsetUnset()} behavior.
     *
     * @param  callable(T): bool  $callback
     * @return self A new filtered DtoCollection instance (not mutated)
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
     * @param  string  $key  The DTO property name to extract
     * @return array<int, mixed> List of property values, preserving order
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
            $keyRef = new \ReflectionProperty($item, $keyField);
            $keyValue = $keyRef->getValue($item);

            // Skip items where the key field is null — null cannot be used
            // as a reliable array key (PHP converts it to empty string "").
            if ($keyValue === null) {
                continue;
            }

            /** @var int|string $keyValue */
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
        $clone = $this->cloneCollection();
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

    /**
     * Re-key the collection by a property value.
     *
     * Returns an associative array where each DTO's array representation
     * is keyed by the value of the specified property. Items with null
     * key values are silently skipped (PHP converts null keys to empty string).
     *
     * Delegates to {@see pluckKey()} internally.
     *
     * Useful for building ID-keyed maps for frontend state or lookup tables:
     *
     *   $keyed = $collection->toArrayBy('id');
     *   // ['42' => ['id' => 42, 'name' => 'Alice'], ...]
     *
     * @param  string  $keyField  The DTO property to use as the array key
     * @return array<int|string, array<string, mixed>>
     *
     * @see pluckKey() For the underlying implementation
     * @see toDictionary() For key-value pair extraction
     */
    public function toArrayBy(string $keyField): array
    {
        return $this->pluckKey($keyField);
    }

    /**
     * Re-key the collection by one property and extract another property as the value.
     *
     * Returns an associative array mapping one DTO property to another.
     * Items where the key property is null are silently skipped.
     *
     * Useful for building lookup maps:
     *
     *   $map = $collection->toDictionary('id', 'name');
     *   // [42 => 'Alice', 43 => 'Bob', ...]
     *
     * @param  string  $keyField  The DTO property to use as the array key
     * @param  string  $valueField  The DTO property to use as the value
     * @return array<int|string, mixed>
     *
     * @see pluckKey() For key-to-array mapping
     * @see toArrayBy() For key-to-full-array mapping
     *
     * @phpstan-return array<int|string, mixed>
     */
    public function toDictionary(string $keyField, string $valueField): array
    {
        $result = [];

        foreach ($this->items as $item) {
            $keyRef = new \ReflectionProperty($item, $keyField);
            $keyValue = $keyRef->getValue($item);

            if ($keyValue === null) {
                continue;
            }

            /** @var int|string $keyValue */
            $result[$keyValue] = (new \ReflectionProperty($item, $valueField))->getValue($item);
        }

        return $result;
    }

    /**
     * Return a new collection with duplicate DTOs removed based on toArray() comparison.
     *
     * Uses strict array equality (`===`) on toArray() output to determine uniqueness.
     * Preserves the first occurrence of each unique DTO and discards subsequent duplicates.
     *
     *   $unique = $collection->unique();  // removes items with identical toArray() output
     *
     * @return self A new DtoCollection with duplicates removed
     *
     * @phpstan-return DtoCollection<DataTransferObject>
     */
    public function unique(): self
    {
        $seen = [];
        $uniqueItems = [];

        foreach ($this->items as $item) {
            $hash = serialize($item->toArray());
            if (! isset($seen[$hash])) {
                $seen[$hash] = true;
                $uniqueItems[] = $item;
            }
        }

        return new self($uniqueItems);
    }

    /**
     * Check if the collection contains a DTO matching the given condition.
     *
     * Uses a callback that receives each DTO and returns true for matches.
     * Short-circuits on the first match for efficiency.
     *
     *   $has = $collection->contains(fn ($dto) => $dto->email === 'a@b.com');
     *
     * @param  callable(T): bool  $callback  Match condition
     * @return bool True if any DTO matches the callback
     */
    public function contains(callable $callback): bool
    {
        foreach ($this->items as $item) {
            if ($callback($item)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Find the first DTO matching a condition, or null if none match.
     *
     * Uses a callback that receives each DTO and returns true for matches.
     * Short-circuits on the first match for efficiency.
     *
     *   $admin = $collection->search(fn ($dto) => $dto->role === 'admin');
     *
     * @template T
     *
     * @param  callable(T): bool  $callback  Search condition
     * @return T|null The matching DTO, or null
     *
     * @phpstan-return T|null
     */
    public function search(callable $callback): ?DataTransferObject
    {
        foreach ($this->items as $item) {
            if ($callback($item)) {
                return $item;
            }
        }

        return null;
    }
}
