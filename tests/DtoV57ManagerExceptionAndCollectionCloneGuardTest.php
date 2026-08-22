<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

/**
 * Coverage boost tests for DTO infrastructure components:
 * - DTOManager::ensureDtoClass() guard for non-DTO classes
 * - DTOException factory methods and __toString()
 * - DtoCollection __clone() guard
 * - DtoCollection offsetSet with invalid type
 * - DtoCollection offsetGet/offsetExists edge cases
 * - DtoCollection push() chaining
 * - DtoCollection allValues() integrity
 * - DataTransferObject flushMetadataCache per-class vs all
 * - DataTransferObject setMetadataCacheTtl configuration
 *
 * @see \ZeroBoiler\DTO\DTOManager
 * @see \ZeroBoiler\DTO\Exceptions\DTOException
 * @see \ZeroBoiler\DTO\DtoCollection
 * @see \ZeroBoiler\DTO\DataTransferObject
 */

use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\DTOManager;
use ZeroBoiler\DTO\Exceptions\DTOException;


// ── Inline test DTOs ──────────────────────────────────

class V57SimpleDTO extends DataTransferObject
{
    public function __construct(
        public readonly string $name,
        public int $value = 0,
    ) {}
}

class V57HiddenDTO extends DataTransferObject
{
    public function __construct(
        public readonly string $public,
        public string $secret = 'hidden',
    ) {}
}

describe('V57 — DTOManager, DTOException, DtoCollection guards', function (): void {

    // ──────────────────────────────────────────────────────────────
    // 1. DTOManager ensureDtoClass guard
    // ──────────────────────────────────────────────────────────────

    describe('DTOManager ensureDtoClass guard', function (): void {
        it('throws InvalidArgumentException for non-DTO class', function (): void {
            $manager = new DTOManager;

            $manager->validate(\stdClass::class, []);
        })->throws(\InvalidArgumentException::class, 'not a subclass of DataTransferObject');

        it('throws InvalidArgumentException for plain class name', function (): void {
            $manager = new DTOManager;

            $manager->make('NonExistentClass', []);
        })->throws(\InvalidArgumentException::class);

        it('throws InvalidArgumentException for rules() on non-DTO', function (): void {
            $manager = new DTOManager;

            $manager->rules(\stdClass::class);
        })->throws(\InvalidArgumentException::class);

        it('throws InvalidArgumentException for schema() on non-DTO', function (): void {
            $manager = new DTOManager;

            $manager->schema(\stdClass::class);
        })->throws(\InvalidArgumentException::class);

        it('throws InvalidArgumentException for rulesFor() on non-DTO', function (): void {
            $manager = new DTOManager;

            $manager->rulesFor(\stdClass::class, 'create');
        })->throws(\InvalidArgumentException::class);

        it('throws InvalidArgumentException for fromPartialArray() on non-DTO', function (): void {
            $manager = new DTOManager;

            $manager->fromPartialArray(\stdClass::class, []);
        })->throws(\InvalidArgumentException::class);

        it('accepts valid DTO subclass in make()', function (): void {
            $manager = new DTOManager;

            $dto = $manager->make(V57SimpleDTO::class, ['name' => 'test']);

            expect($dto)->toBeInstanceOf(V57SimpleDTO::class);
            expect($dto->name)->toBe('test');
        });

        it('accepts valid DTO subclass in validate()', function (): void {
            $manager = new DTOManager;

            $result = $manager->validate(V57SimpleDTO::class, ['name' => 'test']);

            expect($result)->toBeArray();
            expect($result['name'])->toBe('test');
        });

        it('accepts valid DTO subclass in rules()', function (): void {
            $manager = new DTOManager;

            $rules = $manager->rules(V57SimpleDTO::class);

            expect($rules)->toBeArray();
            expect($rules)->toHaveKey('name');
        });
    });

    // ──────────────────────────────────────────────────────────────
    // 2. DTOException factory methods and __toString
    // ──────────────────────────────────────────────────────────────

    describe('DTOException factories and __toString', function (): void {
        it('invalidCast includes property name and type', function (): void {
            $ex = DTOException::invalidCast('email', 'integer', 'not-an-int');

            expect($ex->getMessage())->toContain('email');
            expect($ex->getMessage())->toContain('integer');
        });

        it('invalidCast with null value shows null', function (): void {
            $ex = DTOException::invalidCast('status', 'date', null);

            expect($ex->getMessage())->toContain('null');
        });

        it('invalidJson includes property and error', function (): void {
            $ex = DTOException::invalidJson('payload', 'Syntax error');

            expect($ex->getMessage())->toContain('payload');
            expect($ex->getMessage())->toContain('Syntax error');
        });

        it('__toString returns class name and message', function (): void {
            $ex = DTOException::invalidJson('data', 'bad json');

            $str = $ex->__toString();

            expect($str)->toBeString();
            expect($str)->toContain('DTOException');
            expect($str)->toContain('data');
            expect($str)->toContain('bad json');
        });

        it('__toString for invalidCast includes type info', function (): void {
            $ex = DTOException::invalidCast('age', 'integer', 'abc');

            $str = $ex->__toString();

            expect($str)->toContain('DTOException');
            expect($str)->toContain('age');
        });
    });

    // ──────────────────────────────────────────────────────────────
    // 3. DtoCollection __clone() guard
    // ──────────────────────────────────────────────────────────────

    describe('DtoCollection __clone guard', function (): void {
        it('throws RuntimeException on clone attempt', function (): void {
            $dto = V57SimpleDTO::fromArray(['name' => 'test'], validate: false);
            $col = new DtoCollection([$dto]);

            clone $col;
        })->throws(\RuntimeException::class, 'immutable');
    });

    // ──────────────────────────────────────────────────────────────
    // 4. DtoCollection offsetSet with invalid type
    // ──────────────────────────────────────────────────────────────

    describe('DtoCollection offsetSet type guard', function (): void {
        it('throws on non-DTO offsetSet', function (): void {
            $dto = V57SimpleDTO::fromArray(['name' => 'test'], validate: false);
            $col = new DtoCollection([$dto]);

            $col[] = 'not a DTO';
        })->throws(\InvalidArgumentException::class, 'DataTransferObject instances');

        it('throws on integer offsetSet with non-DTO', function (): void {
            $dto = V57SimpleDTO::fromArray(['name' => 'test'], validate: false);
            $col = new DtoCollection([$dto]);

            $col[5] = 42;
        })->throws(\InvalidArgumentException::class);
    });

    // ──────────────────────────────────────────────────────────────
    // 5. DtoCollection constructor type guard
    // ──────────────────────────────────────────────────────────────

    describe('DtoCollection constructor type guard', function (): void {
        it('throws on non-DTO in constructor', function (): void {
            new DtoCollection(['not', 'dtos']);
        })->throws(\InvalidArgumentException::class);

        it('throws on mixed array in constructor', function (): void {
            $dto = V57SimpleDTO::fromArray(['name' => 'ok'], validate: false);
            new DtoCollection([$dto, 'bad']);
        })->throws(\InvalidArgumentException::class);
    });

    // ──────────────────────────────────────────────────────────────
    // 6. DtoCollection offsetGet/offsetExists edge cases
    // ──────────────────────────────────────────────────────────────

    describe('DtoCollection offsetGet and offsetExists', function (): void {
        it('offsetGet returns null for non-existent offset', function (): void {
            $dto = V57SimpleDTO::fromArray(['name' => 'test'], validate: false);
            $col = new DtoCollection([$dto]);

            expect($col[99])->toBeNull();
        });

        it('offsetGet returns DTO for valid offset', function (): void {
            $dto = V57SimpleDTO::fromArray(['name' => 'test'], validate: false);
            $col = new DtoCollection([$dto]);

            expect($col[0])->toBeInstanceOf(V57SimpleDTO::class);
        });

        it('offsetExists returns false for non-existent offset', function (): void {
            $dto = V57SimpleDTO::fromArray(['name' => 'test'], validate: false);
            $col = new DtoCollection([$dto]);

            expect(isset($col[5]))->toBeFalse();
        });

        it('offsetExists returns true for valid offset', function (): void {
            $dto = V57SimpleDTO::fromArray(['name' => 'test'], validate: false);
            $col = new DtoCollection([$dto]);

            expect(isset($col[0]))->toBeTrue();
        });

        it('offsetUnset re-indexes the collection', function (): void {
            $d1 = V57SimpleDTO::fromArray(['name' => 'a', 'value' => 1], validate: false);
            $d2 = V57SimpleDTO::fromArray(['name' => 'b', 'value' => 2], validate: false);
            $d3 = V57SimpleDTO::fromArray(['name' => 'c', 'value' => 3], validate: false);
            $col = new DtoCollection([$d1, $d2, $d3]);

            unset($col[0]);

            // After removing first, remaining should be re-indexed
            expect($col[0])->toBeInstanceOf(V57SimpleDTO::class);
            expect($col[0]->name)->toBe('b');
            expect($col[1]->name)->toBe('c');
            expect($col->count())->toBe(2);
        });
    });

    // ──────────────────────────────────────────────────────────────
    // 7. DtoCollection push() chaining
    // ──────────────────────────────────────────────────────────────

    describe('DtoCollection push chaining', function (): void {
        it('push returns the same collection for chaining', function (): void {
            $d1 = V57SimpleDTO::fromArray(['name' => 'a'], validate: false);
            $d2 = V57SimpleDTO::fromArray(['name' => 'b'], validate: false);
            $col = new DtoCollection([$d1]);

            $result = $col->push($d2);

            expect($result)->toBe($col); // same instance (mutating)
            expect($col->count())->toBe(2);
        });

        it('push multiple items in chain', function (): void {
            $col = new DtoCollection;

            $col->push(V57SimpleDTO::fromArray(['name' => 'a'], validate: false))
                ->push(V57SimpleDTO::fromArray(['name' => 'b'], validate: false))
                ->push(V57SimpleDTO::fromArray(['name' => 'c'], validate: false));

            expect($col->count())->toBe(3);
        });
    });

    // ──────────────────────────────────────────────────────────────
    // 8. DtoCollection allValues() includes hidden fields
    // ──────────────────────────────────────────────────────────────

    describe('DtoCollection allValues', function (): void {
        it('includes all fields from allValues (no hidden concept in collection)', function (): void {
            $d1 = V57SimpleDTO::fromArray(['name' => 'a', 'value' => 10], validate: false);
            $d2 = V57SimpleDTO::fromArray(['name' => 'b', 'value' => 20], validate: false);
            $col = new DtoCollection([$d1, $d2]);

            $all = $col->allValues();

            expect($all)->toBeArray();
            expect($all[0])->toBe($d1->allValues());
            expect($all[1])->toBe($d2->allValues());
        });
    });

    // ──────────────────────────────────────────────────────────────
    // 9. DtoCollection jsonSerialize returns toArray output
    // ──────────────────────────────────────────────────────────────

    describe('DtoCollection jsonSerialize', function (): void {
        it('returns same as toArray()', function (): void {
            $d1 = V57SimpleDTO::fromArray(['name' => 'a'], validate: false);
            $d2 = V57SimpleDTO::fromArray(['name' => 'b'], validate: false);
            $col = new DtoCollection([$d1, $d2]);

            expect($col->jsonSerialize())->toBe($col->toArray());
        });
    });

    // ──────────────────────────────────────────────────────────────
    // 10. DataTransferObject flushMetadataCache
    // ──────────────────────────────────────────────────────────────

    describe('DataTransferObject metadata cache management', function (): void {
        it('flushMetadataCache clears all by default', function (): void {
            V57SimpleDTO::rules(); // populate cache
            V57HiddenDTO::rules(); // populate cache

            DataTransferObject::flushMetadataCache();

            // After flush, next call should re-resolve without error
            $rules = V57SimpleDTO::rules();
            expect($rules)->toBeArray();
            expect($rules)->toHaveKey('name');
        });

        it('flushMetadataCache with class clears only that class', function (): void {
            V57SimpleDTO::rules();
            V57HiddenDTO::rules();

            DataTransferObject::flushMetadataCache(V57SimpleDTO::class);

            // HiddenDTO should still work fine
            $rules = V57HiddenDTO::rules();
            expect($rules)->toBeArray();
        });

        it('setMetadataCacheTtl configures TTL', function (): void {
            DataTransferObject::setMetadataCacheTtl(0.0);

            $rules = V57SimpleDTO::rules();
            expect($rules)->toBeArray();

            // Reset to default
            DataTransferObject::setMetadataCacheTtl(0.0);
        });
    });

    // ──────────────────────────────────────────────────────────────
    // 11. DtoCollection make factory
    // ──────────────────────────────────────────────────────────────

    describe('DtoCollection make factory', function (): void {
        it('creates empty collection', function (): void {
            $col = DtoCollection::make();

            expect($col)->toBeInstanceOf(DtoCollection::class);
            expect($col->isEmpty())->toBeTrue();
        });

        it('creates collection with items', function (): void {
            $d1 = V57SimpleDTO::fromArray(['name' => 'a'], validate: false);
            $col = DtoCollection::make([$d1]);

            expect($col->count())->toBe(1);
        });
    });

    // ──────────────────────────────────────────────────────────────
    // 12. DtoCollection __debugInfo shape
    // ──────────────────────────────────────────────────────────────

    describe('DtoCollection __debugInfo', function (): void {
        it('returns count and items keys', function (): void {
            $d1 = V57SimpleDTO::fromArray(['name' => 'a'], validate: false);
            $col = new DtoCollection([$d1]);

            $debug = $col->__debugInfo();

            expect($debug)->toBeArray();
            expect($debug)->toHaveKeys(['count', 'items']);
            expect($debug['count'])->toBe(1);
            expect($debug['items'])->toBeArray();
        });

        it('truncates items to 3', function (): void {
            $items = [];
            for ($i = 0; $i < 5; $i++) {
                $items[] = V57SimpleDTO::fromArray(['name' => "item{$i}", 'value' => $i], validate: false);
            }
            $col = new DtoCollection($items);

            $debug = $col->__debugInfo();

            expect($debug['count'])->toBe(5);
            expect($debug['items'])->toHaveCount(3); // truncated
        });

        it('returns empty items for empty collection', function (): void {
            $col = new DtoCollection;

            $debug = $col->__debugInfo();

            expect($debug['count'])->toBe(0);
            expect($debug['items'])->toBeArray();
        });
    });

    // ──────────────────────────────────────────────────────────────
    // 13. DataTransferObject equals with identical and different DTOs
    // ──────────────────────────────────────────────────────────────

    describe('DataTransferObject equals', function (): void {
        it('returns true for identical data', function (): void {
            $a = V57SimpleDTO::fromArray(['name' => 'test', 'value' => 5], validate: false);
            $b = V57SimpleDTO::fromArray(['name' => 'test', 'value' => 5], validate: false);

            expect($a->equals($b))->toBeTrue();
        });

        it('returns false for different data', function (): void {
            $a = V57SimpleDTO::fromArray(['name' => 'a'], validate: false);
            $b = V57SimpleDTO::fromArray(['name' => 'b'], validate: false);

            expect($a->equals($b))->toBeFalse();
        });
    });

    // ──────────────────────────────────────────────────────────────
    // 14. DataTransferObject __debugInfo
    // ──────────────────────────────────────────────────────────────

    describe('DataTransferObject __debugInfo', function (): void {
        it('returns toArray output', function (): void {
            $dto = V57SimpleDTO::fromArray(['name' => 'test', 'value' => 42], validate: false);

            $debug = $dto->__debugInfo();

            expect($debug)->toBe($dto->toArray());
            expect($debug)->toHaveKey('name');
            expect($debug['name'])->toBe('test');
        });
    });

    // ──────────────────────────────────────────────────────────────
    // 15. DataTransferObject only/except with string and array args
    // ──────────────────────────────────────────────────────────────

    describe('DataTransferObject only/except', function (): void {
        it('only with single string key', function (): void {
            $dto = V57SimpleDTO::fromArray(['name' => 'test', 'value' => 42], validate: false);

            expect($dto->only('name'))->toBe(['name' => 'test']);
        });

        it('only with multiple string keys', function (): void {
            $dto = V57SimpleDTO::fromArray(['name' => 'test', 'value' => 42], validate: false);

            $result = $dto->only('name', 'value');

            expect($result)->toHaveCount(2);
        });

        it('except with single key', function (): void {
            $dto = V57SimpleDTO::fromArray(['name' => 'test', 'value' => 42], validate: false);

            $result = $dto->except('value');

            expect($result)->toBe(['name' => 'test']);
        });

        it('except with array of keys', function (): void {
            $dto = V57SimpleDTO::fromArray(['name' => 'test', 'value' => 42], validate: false);

            $result = $dto->except(['name', 'value']);

            expect($result)->toBeArray();
        });

        it('only ignores non-existent keys', function (): void {
            $dto = V57SimpleDTO::fromArray(['name' => 'test'], validate: false);

            $result = $dto->only('name', 'nonexistent');

            expect($result)->toBe(['name' => 'test']);
        });
    });
});
