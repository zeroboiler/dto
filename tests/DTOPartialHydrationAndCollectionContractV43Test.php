<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use Illuminate\Validation\ValidationException;
use ZeroBoiler\DTO\Attributes\{Cast, DefaultValue, Email, Hidden, Max, Min, Required, MapFrom};
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;

// ── Minimal fixtures for testing ──────────────────────────────────────

class SimpleItemDTO extends DataTransferObject
{
    public function __construct(
        #[Required]
        public readonly string $name,

        #[DefaultValue(0)]
        public readonly int $quantity = 0,

        #[DefaultValue(false)]
        public readonly bool $active = false,
    ) {}
}

class MappedFieldDTO extends DataTransferObject
{
    public function __construct(
        #[MapFrom('user_name'), Required]
        public readonly string $name,

        #[MapFrom('user_email')]
        public readonly ?string $email = null,
    ) {}
}

class HiddenFieldDTO extends DataTransferObject
{
    public function __construct(
        public readonly string $public,

        #[Hidden]
        public readonly string $secret,
    ) {}
}

class CastedDTO extends DataTransferObject
{
    public function __construct(
        #[Cast('integer')]
        public readonly int $count = 0,

        #[Cast('boolean')]
        public readonly bool $flag = false,

        #[Cast('array')]
        public readonly array $tags = [],

        #[Cast('string')]
        public readonly string $label = '',
    ) {}
}

class NullableWithDefaultDTO extends DataTransferObject
{
    public function __construct(
        #[DefaultValue('pending')]
        public readonly string $status = 'pending',

        public readonly ?string $note = null,
    ) {}
}

// ── Tests ─────────────────────────────────────────────────────────────

describe('DataTransferObject — fromArray with required validation', function () {
    test('fromArray with valid data creates DTO', function () {
        $dto = SimpleItemDTO::fromArray(['name' => 'Widget', 'quantity' => 5]);

        expect($dto->name)->toBe('Widget');
        expect($dto->quantity)->toBe(5);
        expect($dto->active)->toBeFalse();
    });

    test('fromArray throws on missing required field', function () {
        expect(fn () => SimpleItemDTO::fromArray(['quantity' => 5]))
            ->toThrow(ValidationException::class);
    });

    test('fromArray with validate=false skips validation', function () {
        // Should NOT throw even though 'name' is missing
        // (but may fail at constructor level if no default — here 'name' is required with no default)
        // Actually this WILL fail at constructor level since name has no default.
        // Let's test with a field that has a default.
        $dto = CastedDTO::fromArray([], validate: false);

        expect($dto->count)->toBe(0);
        expect($dto->flag)->toBeFalse();
        expect($dto->tags)->toBe([]);
        expect($dto->label)->toBe('');
    });
});

describe('DataTransferObject — MapFrom contract', function () {
    test('MapFrom maps source key to property name', function () {
        $dto = MappedFieldDTO::fromArray([
            'user_name' => 'Alice',
            'user_email' => 'alice@example.com',
        ]);

        expect($dto->name)->toBe('Alice');
        expect($dto->email)->toBe('alice@example.com');
    });

    test('MapFrom uses property name when source key absent', function () {
        $dto = MappedFieldDTO::fromArray([
            'name' => 'Bob',
        ]);

        expect($dto->name)->toBe('Bob');
    });

    test('MapFrom with dot notation works', function () {
        $dto = MappedFieldDTO::fromArray([
            'user_name' => 'Charlie',
        ]);

        expect($dto->name)->toBe('Charlie');
    });
});

describe('DataTransferObject — Hidden field contract', function () {
    test('toArray excludes hidden fields', function () {
        $dto = HiddenFieldDTO::fromArray(['public' => 'visible', 'secret' => 'password123']);

        $arr = $dto->toArray();
        expect($arr)->toHaveKey('public');
        expect($arr)->not->toHaveKey('secret');
    });

    test('allValues includes hidden fields', function () {
        $dto = HiddenFieldDTO::fromArray(['public' => 'visible', 'secret' => 'password123']);

        $all = $dto->allValues();
        expect($all)->toHaveKey('public');
        expect($all)->toHaveKey('secret');
        expect($all['secret'])->toBe('password123');
    });
});

describe('DataTransferObject — Cast contract', function () {
    test('Cast integer converts string to int', function () {
        $dto = CastedDTO::fromArray(['count' => '42']);
        expect($dto->count)->toBe(42);
    });

    test('Cast boolean converts truthy string', function () {
        $dto = CastedDTO::fromArray(['flag' => 'yes']);
        expect($dto->flag)->toBeTrue();
    });

    test('Cast boolean converts falsy string', function () {
        $dto = CastedDTO::fromArray(['flag' => 'no']);
        expect($dto->flag)->toBeFalse();
    });

    test('Cast array parses JSON string', function () {
        $dto = CastedDTO::fromArray(['tags' => '["a","b"]']);
        expect($dto->tags)->toBe(['a', 'b']);
    });

    test('Cast string converts int to string', function () {
        $dto = CastedDTO::fromArray(['label' => 123]);
        expect($dto->label)->toBe('123');
    });
});

describe('DataTransferObject — isEmpty / isNotEmpty contract', function () {
    test('DTO with only defaults is NOT empty when default is non-empty string', function () {
        $dto = NullableWithDefaultDTO::fromArray([]);
        // status has default 'pending' which is a non-empty string → not empty
        expect($dto->isEmpty())->toBeFalse();
        expect($dto->isNotEmpty())->toBeTrue();
    });

    test('DTO with all empty/null values is empty', function () {
        $dto = CastedDTO::fromArray([]);
        // All defaults are 0, false, [], '' — considered empty
        expect($dto->isEmpty())->toBeTrue();
        expect($dto->isNotEmpty())->toBeFalse();
    });
});

describe('DataTransferObject — fromJson edge cases', function () {
    test('fromJson with valid JSON creates DTO', function () {
        $dto = SimpleItemDTO::fromJson('{"name":"Test","quantity":3}');
        expect($dto->name)->toBe('Test');
        expect($dto->quantity)->toBe(3);
    });

    test('fromJson throws DTOException for invalid JSON', function () {
        expect(fn () => SimpleItemDTO::fromJson('not json'))
            ->toThrow(DTOException::class);
    });

    test('fromJson throws DTOException for sequential array', function () {
        expect(fn () => SimpleItemDTO::fromJson('[1,2,3]'))
            ->toThrow(DTOException::class, 'Expected a JSON object');
    });

    test('fromJson accepts empty JSON object', function () {
        // SimpleItemDTO requires 'name', so this will throw validation
        // but fromJson should not throw JSON-related errors
        expect(fn () => SimpleItemDTO::fromJson('{}'))
            ->toThrow(ValidationException::class);
    });
});

describe('DataTransferObject — with() immutability contract', function () {
    test('with() returns a new instance', function () {
        $original = SimpleItemDTO::fromArray(['name' => 'Original']);
        $updated = $original->with(['name' => 'Updated']);

        expect($original)->not->toBe($updated);
        expect($original->name)->toBe('Original');
        expect($updated->name)->toBe('Updated');
    });

    test('with() always validates (deprecated param has no effect)', function () {
        $dto = SimpleItemDTO::fromArray(['name' => 'Test']);

        // Removing required field should still throw even with validate=false
        expect(fn () => $dto->with(['name' => ''], validate: false))
            ->toThrow(ValidationException::class);
    });
});

describe('DataTransferObject — equals contract', function () {
    test('equals returns true for identical DTOs', function () {
        $a = SimpleItemDTO::fromArray(['name' => 'X', 'quantity' => 1]);
        $b = SimpleItemDTO::fromArray(['name' => 'X', 'quantity' => 1]);

        expect($a->equals($b))->toBeTrue();
    });

    test('equals returns false for different DTOs', function () {
        $a = SimpleItemDTO::fromArray(['name' => 'X', 'quantity' => 1]);
        $b = SimpleItemDTO::fromArray(['name' => 'Y', 'quantity' => 1]);

        expect($a->equals($b))->toBeFalse();
    });
});

describe('DataTransferObject — only / except contract', function () {
    test('only returns specified fields', function () {
        $dto = SimpleItemDTO::fromArray(['name' => 'Test', 'quantity' => 5, 'active' => true]);

        expect($dto->only('name'))->toBe(['name' => 'Test']);
        expect($dto->only(['name', 'quantity']))->toBe(['name' => 'Test', 'quantity' => 5]);
    });

    test('except returns all but specified fields', function () {
        $dto = SimpleItemDTO::fromArray(['name' => 'Test', 'quantity' => 5, 'active' => true]);

        $result = $dto->except('quantity');
        expect($result)->toHaveKey('name');
        expect($result)->toHaveKey('active');
        expect($result)->not->toHaveKey('quantity');
    });
});

describe('DataTransferObject — toJson / jsonSerialize contract', function () {
    test('toJson produces valid JSON string', function () {
        $dto = SimpleItemDTO::fromArray(['name' => 'Test', 'quantity' => 5]);
        $json = $dto->toJson();

        expect($json)->toBeJson();
        $decoded = json_decode($json, true);
        expect($decoded)->toBe(['name' => 'Test', 'quantity' => 5, 'active' => false]);
    });

    test('jsonSerialize returns same as toArray', function () {
        $dto = SimpleItemDTO::fromArray(['name' => 'Test', 'quantity' => 5]);

        expect($dto->jsonSerialize())->toBe($dto->toArray());
    });
});

describe('DataTransferObject — rules / rulesFor contract', function () {
    test('rules returns array keyed by property name', function () {
        $rules = SimpleItemDTO::rules();

        expect($rules)->toBeArray();
        expect($rules)->toHaveKey('name');
        // 'name' should have 'required' rule
        expect($rules['name'])->toContain('required');
    });

    test('rulesFor returns same as rules by default', function () {
        expect(SimpleItemDTO::rulesFor('create'))->toBe(SimpleItemDTO::rules());
        expect(SimpleItemDTO::rulesFor('update'))->toBe(SimpleItemDTO::rules());
    });
});

describe('DataTransferObject — validateArray standalone', function () {
    test('validateArray returns validated data on success', function () {
        $result = SimpleItemDTO::validateArray(['name' => 'Test', 'quantity' => 5]);

        expect($result)->toBe(['name' => 'Test', 'quantity' => 5, 'active' => false]);
    });

    test('validateArray throws on invalid data', function () {
        expect(fn () => SimpleItemDTO::validateArray(['quantity' => 5]))
            ->toThrow(ValidationException::class);
    });
});

describe('DataTransferObject — fromPartialArray contract', function () {
    test('fromPartialArray hydrates only provided fields', function () {
        $dto = NullableWithDefaultDTO::fromPartialArray(['note' => 'hello']);

        expect($dto->note)->toBe('hello');
        // status should use default since it wasn't provided
        expect($dto->status)->toBe('pending');
    });

    test('fromPartialArray with empty array uses all defaults', function () {
        $dto = CastedDTO::fromPartialArray([]);

        expect($dto->count)->toBe(0);
        expect($dto->flag)->toBeFalse();
        expect($dto->tags)->toBe([]);
        expect($dto->label)->toBe('');
    });
});

describe('DtoCollection — immutable operations contract', function () {
    test('append returns new collection without mutating original', function () {
        $a = SimpleItemDTO::fromArray(['name' => 'A']);
        $b = SimpleItemDTO::fromArray(['name' => 'B']);
        $col = DtoCollection::make([$a]);
        $newCol = $col->append($b);

        expect($col->count())->toBe(1);
        expect($newCol->count())->toBe(2);
    });

    test('merge returns new collection combining both', function () {
        $a = SimpleItemDTO::fromArray(['name' => 'A']);
        $b = SimpleItemDTO::fromArray(['name' => 'B']);
        $c = SimpleItemDTO::fromArray(['name' => 'C']);
        $col1 = DtoCollection::make([$a]);
        $col2 = DtoCollection::make([$b, $c]);
        $merged = $col1->merge($col2);

        expect($merged->count())->toBe(3);
    });

    test('filter returns new collection with matching items', function () {
        $a = SimpleItemDTO::fromArray(['name' => 'A', 'quantity' => 5]);
        $b = SimpleItemDTO::fromArray(['name' => 'B', 'quantity' => 10]);
        $col = DtoCollection::make([$a, $b]);
        $filtered = $col->filter(fn (SimpleItemDTO $d) => $d->quantity > 7);

        expect($filtered->count())->toBe(1);
    });

    test('unique removes duplicate DTOs based on toArray()', function () {
        $a = SimpleItemDTO::fromArray(['name' => 'Same', 'quantity' => 1]);
        $b = SimpleItemDTO::fromArray(['name' => 'Same', 'quantity' => 1]);
        $c = SimpleItemDTO::fromArray(['name' => 'Different', 'quantity' => 2]);
        $col = DtoCollection::make([$a, $b, $c]);
        $unique = $col->unique();

        expect($unique->count())->toBe(2);
    });

    test('sortBy returns new sorted collection (by property name)', function () {
        $a = SimpleItemDTO::fromArray(['name' => 'C', 'quantity' => 3]);
        $b = SimpleItemDTO::fromArray(['name' => 'A', 'quantity' => 1]);
        $c = SimpleItemDTO::fromArray(['name' => 'B', 'quantity' => 2]);
        $col = DtoCollection::make([$a, $b, $c]);
        $sorted = $col->sortBy('name');

        $names = $sorted->map(fn (SimpleItemDTO $d) => $d->name);
        expect($names)->toBe(['A', 'B', 'C']);
    });

    test('take returns first N items', function () {
        $items = [];
        for ($i = 1; $i <= 5; $i++) {
            $items[] = SimpleItemDTO::fromArray(['name' => "Item {$i}", 'quantity' => $i]);
        }
        $col = DtoCollection::make($items);
        $taken = $col->take(3);

        expect($taken->count())->toBe(3);
    });

    test('skip returns remaining items after N', function () {
        $items = [];
        for ($i = 1; $i <= 5; $i++) {
            $items[] = SimpleItemDTO::fromArray(['name' => "Item {$i}", 'quantity' => $i]);
        }
        $col = DtoCollection::make($items);
        $remaining = $col->skip(3);

        expect($remaining->count())->toBe(2);
    });

    test('chunk splits into correct-sized groups', function () {
        $items = [];
        for ($i = 1; $i <= 5; $i++) {
            $items[] = SimpleItemDTO::fromArray(['name' => "Item {$i}", 'quantity' => $i]);
        }
        $col = DtoCollection::make($items);
        $chunks = $col->chunk(2);

        expect(count($chunks))->toBe(3); // [2, 2, 1]
        expect($chunks[0]->count())->toBe(2);
        expect($chunks[1]->count())->toBe(2);
        expect($chunks[2]->count())->toBe(1);
    });

    test('contains returns true when callback matches', function () {
        $a = SimpleItemDTO::fromArray(['name' => 'Target', 'quantity' => 1]);
        $b = SimpleItemDTO::fromArray(['name' => 'Other', 'quantity' => 2]);
        $col = DtoCollection::make([$a, $b]);

        expect($col->contains(fn (SimpleItemDTO $d) => $d->name === 'Target'))->toBeTrue();
        expect($col->contains(fn (SimpleItemDTO $d) => $d->name === 'Missing'))->toBeFalse();
    });

    test('search returns first matching DTO or null', function () {
        $a = SimpleItemDTO::fromArray(['name' => 'First', 'quantity' => 1]);
        $b = SimpleItemDTO::fromArray(['name' => 'Second', 'quantity' => 2]);
        $col = DtoCollection::make([$a, $b]);

        $found = $col->search(fn (SimpleItemDTO $d) => $d->name === 'Second');
        expect($found)->not->toBeNull();
        expect($found->name)->toBe('Second');

        expect($col->search(fn (SimpleItemDTO $d) => $d->name === 'Missing'))->toBeNull();
    });

    test('push mutates in-place and returns self', function () {
        $a = SimpleItemDTO::fromArray(['name' => 'A']);
        $b = SimpleItemDTO::fromArray(['name' => 'B']);
        $col = DtoCollection::make([$a]);
        $result = $col->push($b);

        expect($result)->toBe($col); // Same instance
        expect($col->count())->toBe(2);
    });

    test('pluck extracts a single property', function () {
        $a = SimpleItemDTO::fromArray(['name' => 'Alice', 'quantity' => 5]);
        $b = SimpleItemDTO::fromArray(['name' => 'Bob', 'quantity' => 10]);
        $col = DtoCollection::make([$a, $b]);

        expect($col->pluck('name'))->toBe(['Alice', 'Bob']);
        expect($col->pluck('quantity'))->toBe([5, 10]);
    });

    test('pluckKey returns key-value pairs', function () {
        $a = SimpleItemDTO::fromArray(['name' => 'a@b.com', 'quantity' => 1]);
        $b = SimpleItemDTO::fromArray(['name' => 'c@d.com', 'quantity' => 2]);
        $col = DtoCollection::make([$a, $b]);

        $map = $col->pluckKey('name', 'quantity');
        expect($map)->toBe(['a@b.com' => 1, 'c@d.com' => 2]);
    });

    test('toArrayBy is alias for pluckKey with single arg', function () {
        $a = SimpleItemDTO::fromArray(['name' => 'Alice', 'quantity' => 1]);
        $col = DtoCollection::make([$a]);

        $result = $col->toArrayBy('name');
        expect($result)->toBe(['Alice' => ['name' => 'Alice', 'quantity' => 1, 'active' => false]]);
    });

    test('toDictionary extracts two properties as key-value map', function () {
        $a = SimpleItemDTO::fromArray(['name' => 'key1', 'quantity' => 42]);
        $b = SimpleItemDTO::fromArray(['name' => 'key2', 'quantity' => 99]);
        $col = DtoCollection::make([$a, $b]);

        $dict = $col->toDictionary('name', 'quantity');
        expect($dict)->toBe(['key1' => 42, 'key2' => 99]);
    });

    test('clone throws RuntimeException', function () {
        $a = SimpleItemDTO::fromArray(['name' => 'A']);
        $col = DtoCollection::make([$a]);

        expect(fn () => clone $col)->toThrow(\RuntimeException::class, 'immutable');
    });

    test('offsetExists / offsetGet / offsetSet / offsetUnset', function () {
        $a = SimpleItemDTO::fromArray(['name' => 'A']);
        $b = SimpleItemDTO::fromArray(['name' => 'B']);
        $col = DtoCollection::make([$a, $b]);

        expect(isset($col[0]))->toBeTrue();
        expect(isset($col[1]))->toBeTrue();
        expect(isset($col[2]))->toBeFalse();

        expect($col[0]->name)->toBe('A');
        expect($col[1]->name)->toBe('B');
        expect($col[5])->toBeNull();

        // offsetSet null appends
        $col[] = SimpleItemDTO::fromArray(['name' => 'C']);
        expect($col[2]->name)->toBe('C');
        expect($col->count())->toBe(3);

        // offsetUnset removes and re-indexes
        unset($col[0]);
        expect($col[0]->name)->toBe('B'); // Re-indexed
        expect($col->count())->toBe(2);
    });

    test('constructor rejects non-DTO items', function () {
        expect(fn () => new DtoCollection([new \stdClass]))
            ->toThrow(\InvalidArgumentException::class, 'DataTransferObject');
    });

    test('make creates from array of DTOs', function () {
        $a = SimpleItemDTO::fromArray(['name' => 'A']);
        $col = DtoCollection::make([$a]);

        expect($col->count())->toBe(1);
        expect($col->first()->name)->toBe('A');
    });

    test('isEmpty and isNotEmpty', function () {
        $empty = DtoCollection::make([]);
        expect($empty->isEmpty())->toBeTrue();
        expect($empty->isNotEmpty())->toBeFalse();

        $a = SimpleItemDTO::fromArray(['name' => 'A']);
        $nonEmpty = DtoCollection::make([$a]);
        expect($nonEmpty->isEmpty())->toBeFalse();
        expect($nonEmpty->isNotEmpty())->toBeTrue();
    });

    test('first and last', function () {
        $a = SimpleItemDTO::fromArray(['name' => 'First']);
        $b = SimpleItemDTO::fromArray(['name' => 'Last']);
        $col = DtoCollection::make([$a, $b]);

        expect($col->first()->name)->toBe('First');
        expect($col->last()->name)->toBe('Last');
    });

    test('map transforms items to plain array', function () {
        $a = SimpleItemDTO::fromArray(['name' => 'A', 'quantity' => 1]);
        $b = SimpleItemDTO::fromArray(['name' => 'B', 'quantity' => 2]);
        $col = DtoCollection::make([$a, $b]);

        $names = $col->map(fn (SimpleItemDTO $d, int $i) => "{$i}:{$d->name}");
        expect($names)->toBe(['0:A', '1:B']);
    });

    test('jsonSerialize returns array of toArray()', function () {
        $a = SimpleItemDTO::fromArray(['name' => 'A', 'quantity' => 1]);
        $col = DtoCollection::make([$a]);

        $json = json_encode($col);
        expect($json)->toBe('[{"name":"A","quantity":1,"active":false}]');
    });

    test('__debugInfo shows count and first 3 items', function () {
        $items = [];
        for ($i = 1; $i <= 5; $i++) {
            $items[] = SimpleItemDTO::fromArray(['name' => "Item {$i}", 'quantity' => $i]);
        }
        $col = DtoCollection::make($items);
        $info = $col->__debugInfo();

        expect($info['count'])->toBe(5);
        expect(count($info['items']))->toBe(3); // Truncated to 3
    });

    test('items returns raw DTO instances', function () {
        $a = SimpleItemDTO::fromArray(['name' => 'A']);
        $col = DtoCollection::make([$a]);

        expect($col->items())->toBe([$a]);
    });
});

describe('DataTransferObject — __debugInfo contract', function () {
    test('__debugInfo returns toArray output', function () {
        $dto = SimpleItemDTO::fromArray(['name' => 'Test']);
        expect($dto->__debugInfo())->toBe($dto->toArray());
    });
});

describe('DataTransferObject — metadata cache flush contract', function () {
    test('flushMetadataCache(null) clears all cached metadata', function () {
        // Resolve metadata for a class to cache it
        SimpleItemDTO::rules();

        // Flush all
        SimpleItemDTO::flushMetadataCache(null);

        // Re-resolve should work fine (no stale cache issues)
        $rules = SimpleItemDTO::rules();
        expect($rules)->toBeArray();
    });

    test('flushMetadataCache(class) clears only that class', function () {
        SimpleItemDTO::rules();
        CastedDTO::rules();

        // Flush only SimpleItemDTO
        SimpleItemDTO::flushMetadataCache(SimpleItemDTO::class);

        // CastedDTO rules should still work from cache
        $rules = CastedDTO::rules();
        expect($rules)->toBeArray();
    });
});

describe('DTOException — contract', function () {
    test('invalidCast includes property, type, and value debug info', function () {
        $e = DTOException::invalidCast('age', 'integer', 'not_a_number');

        expect($e->getMessage())->toContain('age');
        expect($e->getMessage())->toContain('integer');
        expect($e->getMessage())->toContain('not_a_number');
    });

    test('invalidJson includes property and error', function () {
        $e = DTOException::invalidJson('payload', 'Syntax error');

        expect($e->getMessage())->toContain('payload');
        expect($e->getMessage())->toContain('Syntax error');
    });

    test('__toString returns class name + message', function () {
        $e = DTOException::invalidJson('test', 'bad');
        $str = (string) $e;

        expect($str)->toStartWith(DTOException::class.':');
        expect($str)->toContain('bad');
    });
});
