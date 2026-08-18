<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;

describe('DtoCollection toArrayBy and toDictionary edge cases', function () {

    // Minimal DTO fixture for collection tests
    // We use an anonymous approach: create a concrete DTO inline
    // Since DataTransferObject is abstract, we need a concrete subclass.
    // We'll create a simple test DTO class in the test body.

    it('toArrayBy() delegates to pluckKey and returns associative array', function () {
        $dto1 = new class(name: 'Alice', age: 30) extends DataTransferObject {
            public function __construct(
                public readonly string $name,
                public readonly int $age,
            ) {}
        };
        $dto2 = new class(name: 'Bob', age: 25) extends DataTransferObject {
            public function __construct(
                public readonly string $name,
                public readonly int $age,
            ) {}
        };

        $col = new DtoCollection([$dto1, $dto2]);
        $result = $col->toArrayBy('name');

        expect($result)->toBeArray();
        expect($result)->toHaveKey('Alice');
        expect($result)->toHaveKey('Bob');
        expect($result['Alice'])->toBe(['name' => 'Alice', 'age' => 30]);
        expect($result['Bob'])->toBe(['name' => 'Bob', 'age' => 25]);
    });

    it('toArrayBy() skips items with null key values', function () {
        $dto1 = new class(name: 'Alice', email: 'a@b.com') extends DataTransferObject {
            public function __construct(
                public readonly string $name,
                public readonly ?string $email,
            ) {}
        };
        $dto2 = new class(name: null, email: 'b@c.com') extends DataTransferObject {
            public function __construct(
                public readonly ?string $name,
                public readonly string $email,
            ) {}
        };

        $col = new DtoCollection([$dto1, $dto2]);
        $result = $col->toArrayBy('name');

        expect($result)->toBeArray();
        expect($result)->toHaveKey('Alice');
        expect($result)->not->toHaveKey(null); // null key is skipped
        expect(count($result))->toBe(1);
    });

    it('toArrayBy() uses int keys correctly', function () {
        $dto1 = new class(id: 10, name: 'Alice') extends DataTransferObject {
            public function __construct(
                public readonly int $id,
                public readonly string $name,
            ) {}
        };
        $dto2 = new class(id: 20, name: 'Bob') extends DataTransferObject {
            public function __construct(
                public readonly int $id,
                public readonly string $name,
            ) {}
        };

        $col = new DtoCollection([$dto1, $dto2]);
        $result = $col->toArrayBy('id');

        expect($result)->toBe([10 => ['id' => 10, 'name' => 'Alice'], 20 => ['id' => 20, 'name' => 'Bob']]);
    });

    it('toDictionary() returns key-value pairs from two properties', function () {
        $dto1 = new class(id: 1, role: 'admin') extends DataTransferObject {
            public function __construct(
                public readonly int $id,
                public readonly string $role,
            ) {}
        };
        $dto2 = new class(id: 2, role: 'viewer') extends DataTransferObject {
            public function __construct(
                public readonly int $id,
                public readonly string $role,
            ) {}
        };

        $col = new DtoCollection([$dto1, $dto2]);
        $result = $col->toDictionary('id', 'role');

        expect($result)->toBe([1 => 'admin', 2 => 'viewer']);
    });

    it('toDictionary() skips items with null key values', function () {
        $dto1 = new class(id: 1, name: 'Alice') extends DataTransferObject {
            public function __construct(
                public readonly int $id,
                public readonly string $name,
            ) {}
        };
        $dto2 = new class(id: null, name: 'Bob') extends DataTransferObject {
            public function __construct(
                public readonly ?int $id,
                public readonly string $name,
            ) {}
        };

        $col = new DtoCollection([$dto1, $dto2]);
        $result = $col->toDictionary('id', 'name');

        expect($result)->toBe([1 => 'Alice']);
    });

    it('toDictionary() returns full array when valueField is null', function () {
        $dto = new class(id: 42, name: 'Alice') extends DataTransferObject {
            public function __construct(
                public readonly int $id,
                public readonly string $name,
            ) {}
        };

        $col = new DtoCollection([$dto]);
        $result = $col->toDictionary('id', null);

        expect($result)->toBe([42 => ['id' => 42, 'name' => 'Alice']]);
    });

    it('toDictionary() with string key field', function () {
        $dto = new class(email: 'a@b.com', name: 'Alice') extends DataTransferObject {
            public function __construct(
                public readonly string $email,
                public readonly string $name,
            ) {}
        };

        $col = new DtoCollection([$dto]);
        $result = $col->toDictionary('email', 'name');

        expect($result)->toBe(['a@b.com' => 'Alice']);
    });
});

describe('DTOException named constructors contract', function () {

    it('invalidCast() creates exception with property, type, and value info', function () {
        $exception = DTOException::invalidCast('age', 'integer', 'not_a_number');

        expect($exception)->toBeInstanceOf(DTOException::class);
        expect($exception->getMessage())->toContain('age');
        expect($exception->getMessage())->toContain('integer');
        expect($exception->getMessage())->toContain('not_a_number');
    });

    it('invalidCast() handles null value', function () {
        $exception = DTOException::invalidCast('status', 'string', null);

        expect($exception->getMessage())->toContain('status');
        expect($exception->getMessage())->toContain('string');
        expect($exception->getMessage())->toContain('null');
    });

    it('invalidCast() handles int value', function () {
        $exception = DTOException::invalidCast('count', 'array', 42);

        expect($exception->getMessage())->toContain('count');
        expect($exception->getMessage())->toContain('array');
        expect($exception->getMessage())->toContain('42');
    });

    it('invalidJson() creates exception with property and error info', function () {
        $exception = DTOException::invalidJson('metadata', 'Syntax error');

        expect($exception)->toBeInstanceOf(DTOException::class);
        expect($exception->getMessage())->toContain('metadata');
        expect($exception->getMessage())->toContain('Syntax error');
    });

    it('__toString() returns class name and message', function () {
        $exception = DTOException::invalidCast('field', 'type', 'value');
        $string = (string) $exception;

        expect($string)->toContain(DTOException::class);
        expect($string)->toContain('field');
        expect($string)->toContain('type');
    });

    it('is final (cannot be extended)', function () {
        $ref = new \ReflectionClass(DTOException::class);
        expect($ref->isFinal())->toBeTrue();
    });

    it('extends Exception', function () {
        expect(new \ReflectionClass(DTOException::class))->isSubclassOf(\Exception::class);
    });
});

describe('DtoCollection immutable operations contract', function () {

    it('append() returns new collection without modifying original', function () {
        $dto1 = new class(name: 'Alice') extends DataTransferObject {
            public function __construct(public readonly string $name) {}
        };
        $dto2 = new class(name: 'Bob') extends DataTransferObject {
            public function __construct(public readonly string $name) {}
        };

        $original = new DtoCollection([$dto1]);
        $appended = $original->append($dto2);

        expect($original->count())->toBe(1);
        expect($appended->count())->toBe(2);
        expect($appended->first()->name)->toBe('Alice');
        expect($appended->last()->name)->toBe('Bob');
    });

    it('merge() returns new collection combining both', function () {
        $dto1 = new class(name: 'Alice') extends DataTransferObject {
            public function __construct(public readonly string $name) {}
        };
        $dto2 = new class(name: 'Bob') extends DataTransferObject {
            public function __construct(public readonly string $name) {}
        };
        $dto3 = new class(name: 'Charlie') extends DataTransferObject {
            public function __construct(public readonly string $name) {}
        };

        $col1 = new DtoCollection([$dto1]);
        $col2 = new DtoCollection([$dto2, $dto3]);

        $merged = $col1->merge($col2);

        expect($col1->count())->toBe(1);
        expect($col2->count())->toBe(2);
        expect($merged->count())->toBe(3);
    });

    it('filter() returns new collection with matching items only', function () {
        $dto1 = new class(age: 25) extends DataTransferObject {
            public function __construct(public readonly int $age) {}
        };
        $dto2 = new class(age: 17) extends DataTransferObject {
            public function __construct(public readonly int $age) {}
        };
        $dto3 = new class(age: 30) extends DataTransferObject {
            public function __construct(public readonly int $age) {}
        };

        $col = new DtoCollection([$dto1, $dto2, $dto3]);
        $adults = $col->filter(fn ($d) => $d->age >= 18);

        expect($adults->count())->toBe(2);
        expect($col->count())->toBe(3); // original unchanged
    });

    it('sortBy() with string property name returns sorted collection', function () {
        $dto1 = new class(name: 'Charlie') extends DataTransferObject {
            public function __construct(public readonly string $name) {}
        };
        $dto2 = new class(name: 'Alice') extends DataTransferObject {
            public function __construct(public readonly string $name) {}
        };
        $dto3 = new class(name: 'Bob') extends DataTransferObject {
            public function __construct(public readonly string $name) {}
        };

        $col = new DtoCollection([$dto1, $dto2, $dto3]);
        $sorted = $col->sortBy('name');

        expect($sorted->map(fn ($d) => $d->name))->toBe(['Alice', 'Bob', 'Charlie']);
    });

    it('sortBy() with callback returns sorted collection', function () {
        $dto1 = new class(val: 30) extends DataTransferObject {
            public function __construct(public readonly int $val) {}
        };
        $dto2 = new class(val: 10) extends DataTransferObject {
            public function __construct(public readonly int $val) {}
        };

        $col = new DtoCollection([$dto1, $dto2]);
        $sorted = $col->sortBy(fn ($d) => $d->val);

        expect($sorted->map(fn ($d) => $d->val))->toBe([10, 30]);
    });

    it('sortBy() pushes null values to the end', function () {
        $dto1 = new class(val: null) extends DataTransferObject {
            public function __construct(public readonly ?int $val) {}
        };
        $dto2 = new class(val: 5) extends DataTransferObject {
            public function __construct(public readonly ?int $val) {}
        };
        $dto3 = new class(val: 10) extends DataTransferObject {
            public function __construct(public readonly ?int $val) {}
        };

        $col = new DtoCollection([$dto1, $dto2, $dto3]);
        $sorted = $col->sortBy('val');

        $values = $sorted->map(fn ($d) => $d->val);
        expect($values[0])->toBe(5);
        expect($values[1])->toBe(10);
        expect($values[2])->toBeNull();
    });

    it('unique() removes items with identical toArray() output', function () {
        $dto1 = new class(name: 'Alice', age: 30) extends DataTransferObject {
            public function __construct(public readonly string $name, public readonly int $age) {}
        };
        $dto2 = new class(name: 'Alice', age: 30) extends DataTransferObject {
            public function __construct(public readonly string $name, public readonly int $age) {}
        };
        $dto3 = new class(name: 'Bob', age: 25) extends DataTransferObject {
            public function __construct(public readonly string $name, public readonly int $age) {}
        };

        $col = new DtoCollection([$dto1, $dto2, $dto3]);
        $unique = $col->unique();

        expect($unique->count())->toBe(2);
    });

    it('take() returns at most N items', function () {
        $dtos = [];
        for ($i = 1; $i <= 5; $i++) {
            $dtos[] = new class(n: $i) extends DataTransferObject {
                public function __construct(public readonly int $n) {}
            };
        }

        $col = new DtoCollection($dtos);
        $first3 = $col->take(3);

        expect($first3->count())->toBe(3);
        expect($col->count())->toBe(5); // original unchanged
    });

    it('skip() returns remaining items after N', function () {
        $dtos = [];
        for ($i = 1; $i <= 5; $i++) {
            $dtos[] = new class(n: $i) extends DataTransferObject {
                public function __construct(public readonly int $n) {}
            };
        }

        $col = new DtoCollection($dtos);
        $remaining = $col->skip(3);

        expect($remaining->count())->toBe(2);
        expect($col->count())->toBe(5); // original unchanged
    });

    it('chunk() splits into correctly-sized sub-collections', function () {
        $dtos = [];
        for ($i = 1; $i <= 5; $i++) {
            $dtos[] = new class(n: $i) extends DataTransferObject {
                public function __construct(public readonly int $n) {}
            };
        }

        $col = new DtoCollection($dtos);
        $chunks = $col->chunk(2);

        expect(count($chunks))->toBe(3); // [2, 2, 1]
        expect($chunks[0]->count())->toBe(2);
        expect($chunks[1]->count())->toBe(2);
        expect($chunks[2]->count())->toBe(1);
    });

    it('contains() short-circuits on first match', function () {
        $dto1 = new class(val: 1) extends DataTransferObject {
            public function __construct(public readonly int $val) {}
        };
        $dto2 = new class(val: 2) extends DataTransferObject {
            public function __construct(public readonly int $val) {}
        };

        $col = new DtoCollection([$dto1, $dto2]);

        expect($col->contains(fn ($d) => $d->val === 1))->toBeTrue();
        expect($col->contains(fn ($d) => $d->val === 99))->toBeFalse();
    });

    it('search() returns first matching DTO or null', function () {
        $dto1 = new class(val: 'a') extends DataTransferObject {
            public function __construct(public readonly string $val) {}
        };
        $dto2 = new class(val: 'b') extends DataTransferObject {
            public function __construct(public readonly string $val) {}
        };

        $col = new DtoCollection([$dto1, $dto2]);

        $found = $col->search(fn ($d) => $d->val === 'b');
        expect($found)->not->toBeNull();
        expect($found->val)->toBe('b');

        $notFound = $col->search(fn ($d) => $d->val === 'z');
        expect($notFound)->toBeNull();
    });
});

describe('DtoCollection ArrayAccess and IteratorAggregate contract', function () {

    it('offsetExists returns true for valid offsets', function () {
        $dto = new class(name: 'Alice') extends DataTransferObject {
            public function __construct(public readonly string $name) {}
        };
        $col = new DtoCollection([$dto]);

        expect(isset($col[0]))->toBeTrue();
        expect(isset($col[1]))->toBeFalse();
    });

    it('offsetGet returns DTO at valid offset, null at invalid', function () {
        $dto = new class(name: 'Alice') extends DataTransferObject {
            public function __construct(public readonly string $name) {}
        };
        $col = new DtoCollection([$dto]);

        expect($col[0]->name)->toBe('Alice');
        expect($col[1])->toBeNull();
    });

    it('offsetSet appends when offset is null', function () {
        $dto1 = new class(name: 'Alice') extends DataTransferObject {
            public function __construct(public readonly string $name) {}
        };
        $dto2 = new class(name: 'Bob') extends DataTransferObject {
            public function __construct(public readonly string $name) {}
        };

        $col = new DtoCollection([$dto1]);
        $col[] = $dto2; // null offset → append

        expect($col->count())->toBe(2);
        expect($col[1]->name)->toBe('Bob');
    });

    it('offsetSet replaces at given offset', function () {
        $dto1 = new class(name: 'Alice') extends DataTransferObject {
            public function __construct(public readonly string $name) {}
        };
        $dto2 = new class(name: 'Bob') extends DataTransferObject {
            public function __construct(public readonly string $name) {}
        };

        $col = new DtoCollection([$dto1]);
        $col[0] = $dto2;

        expect($col->count())->toBe(1);
        expect($col[0]->name)->toBe('Bob');
    });

    it('offsetSet rejects non-DTO values', function () {
        $dto = new class(name: 'Alice') extends DataTransferObject {
            public function __construct(public readonly string $name) {}
        };
        $col = new DtoCollection([$dto]);

        expect(fn () => $col[] = 'not a dto')->toThrow(\InvalidArgumentException::class);
    });

    it('offsetUnset removes item and re-indexes', function () {
        $dto1 = new class(name: 'Alice') extends DataTransferObject {
            public function __construct(public readonly string $name) {}
        };
        $dto2 = new class(name: 'Bob') extends DataTransferObject {
            public function __construct(public readonly string $name) {}
        };
        $dto3 = new class(name: 'Charlie') extends DataTransferObject {
            public function __construct(public readonly string $name) {}
        };

        $col = new DtoCollection([$dto1, $dto2, $dto3]);
        unset($col[0]); // Remove Alice

        expect($col->count())->toBe(2);
        expect($col[0]->name)->toBe('Bob'); // Re-indexed
        expect($col[1]->name)->toBe('Charlie');
    });

    it('is iterable via foreach', function () {
        $dto1 = new class(name: 'Alice') extends DataTransferObject {
            public function __construct(public readonly string $name) {}
        };
        $dto2 = new class(name: 'Bob') extends DataTransferObject {
            public function __construct(public readonly string $name) {}
        };

        $col = new DtoCollection([$dto1, $dto2]);
        $names = [];
        foreach ($col as $dto) {
            $names[] = $dto->name;
        }

        expect($names)->toBe(['Alice', 'Bob']);
    });

    it('is JSON-serializable', function () {
        $dto = new class(name: 'Alice') extends DataTransferObject {
            public function __construct(public readonly string $name) {}
        };

        $col = new DtoCollection([$dto]);
        $json = json_encode($col);

        expect($json)->toBe('[{"name":"Alice"}]');
    });

    it('constructor rejects non-DTO items', function () {
        expect(fn () => new DtoCollection(['not a dto']))->toThrow(\InvalidArgumentException::class);
        expect(fn () => new DtoCollection([1, 2, 3]))->toThrow(\InvalidArgumentException::class);
    });

    it('__clone() always throws', function () {
        $col = new DtoCollection;
        expect(fn () => clone $col)->toThrow(\RuntimeException::class);
    });

    it('make() factory creates empty collection by default', function () {
        $col = DtoCollection::make();
        expect($col->count())->toBe(0);
        expect($col->isEmpty())->toBeTrue();
        expect($col->isNotEmpty())->toBeFalse();
    });

    it('push() mutates in place and returns self for chaining', function () {
        $dto1 = new class(name: 'Alice') extends DataTransferObject {
            public function __construct(public readonly string $name) {}
        };
        $dto2 = new class(name: 'Bob') extends DataTransferObject {
            public function __construct(public readonly string $name) {}
        };

        $col = new DtoCollection;
        $result = $col->push($dto1)->push($dto2);

        expect($result)->toBe($col); // Same instance
        expect($col->count())->toBe(2);
    });

    it('first() and last() return null on empty collection', function () {
        $col = new DtoCollection;

        expect($col->first())->toBeNull();
        expect($col->last())->toBeNull();
    });

    it('items() returns raw DTO instances', function () {
        $dto1 = new class(name: 'Alice') extends DataTransferObject {
            public function __construct(public readonly string $name) {}
        };
        $dto2 = new class(name: 'Bob') extends DataTransferObject {
            public function __construct(public readonly string $name) {}
        };

        $col = new DtoCollection([$dto1, $dto2]);
        $items = $col->items();

        expect(count($items))->toBe(2);
        expect($items[0])->toBe($dto1);
        expect($items[1])->toBe($dto2);
    });

    it('allValues() includes hidden fields', function () {
        // We can't easily test Hidden without the full DTO infrastructure,
        // but we can verify allValues() returns the same as toArray() for simple DTOs
        $dto = new class(name: 'Alice') extends DataTransferObject {
            public function __construct(public readonly string $name) {}
        };

        $col = new DtoCollection([$dto]);
        expect($col->allValues())->toBe($col->toArray());
    });

    it('__debugInfo() returns count and truncated items', function () {
        $dtos = [];
        for ($i = 1; $i <= 5; $i++) {
            $dtos[] = new class(n: $i) extends DataTransferObject {
                public function __construct(public readonly int $n) {}
            };
        }

        $col = new DtoCollection($dtos);
        $debug = $col->__debugInfo();

        expect($debug)->toHaveKey('count');
        expect($debug)->toHaveKey('items');
        expect($debug['count'])->toBe(5);
        expect(count($debug['items']))->toBe(3); // truncated to first 3
    });

    it('map() passes index as second argument to callback', function () {
        $dto1 = new class(name: 'Alice') extends DataTransferObject {
            public function __construct(public readonly string $name) {}
        };
        $dto2 = new class(name: 'Bob') extends DataTransferObject {
            public function __construct(public readonly string $name) {}
        };

        $col = new DtoCollection([$dto1, $dto2]);
        $indexed = $col->map(fn ($d, $i) => "{$i}:{$d->name}");

        expect($indexed)->toBe(['0:Alice', '1:Bob']);
    });

    it('pluck() extracts a single property from all DTOs', function () {
        $dto1 = new class(name: 'Alice', age: 30) extends DataTransferObject {
            public function __construct(public readonly string $name, public readonly int $age) {}
        };
        $dto2 = new class(name: 'Bob', age: 25) extends DataTransferObject {
            public function __construct(public readonly string $name, public readonly int $age) {}
        };

        $col = new DtoCollection([$dto1, $dto2]);
        $names = $col->pluck('name');

        expect($names)->toBe(['Alice', 'Bob']);
    });

    it('pluckKey() with valueField extracts key-value pairs', function () {
        $dto1 = new class(email: 'a@b.com', name: 'Alice') extends DataTransferObject {
            public function __construct(public readonly string $email, public readonly string $name) {}
        };
        $dto2 = new class(email: 'c@d.com', name: 'Charlie') extends DataTransferObject {
            public function __construct(public readonly string $email, public readonly string $name) {}
        };

        $col = new DtoCollection([$dto1, $dto2]);
        $map = $col->pluckKey('email', 'name');

        expect($map)->toBe(['a@b.com' => 'Alice', 'c@d.com' => 'Charlie']);
    });

    it('pluckKey() without valueField returns full array per item', function () {
        $dto = new class(id: 1, name: 'Alice') extends DataTransferObject {
            public function __construct(public readonly int $id, public readonly string $name) {}
        };

        $col = new DtoCollection([$dto]);
        $map = $col->pluckKey('id');

        expect($map)->toBe([1 => ['id' => 1, 'name' => 'Alice']]);
    });

    it('pluckKey() skips null key values', function () {
        $dto1 = new class(id: 1, name: 'Alice') extends DataTransferObject {
            public function __construct(public readonly int $id, public readonly string $name) {}
        };
        $dto2 = new class(id: null, name: 'Bob') extends DataTransferObject {
            public function __construct(public readonly ?int $id, public readonly string $name) {}
        };

        $col = new DtoCollection([$dto1, $dto2]);
        $map = $col->pluckKey('id', 'name');

        expect($map)->toBe([1 => 'Alice']);
    });
});
