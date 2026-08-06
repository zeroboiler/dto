<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Tests;

use Illuminate\Validation\ValidationException;
use ZeroBoiler\DTO\Attributes\Accepted;
use ZeroBoiler\DTO\Attributes\Between;
use ZeroBoiler\DTO\Attributes\Boolean;
use ZeroBoiler\DTO\Attributes\Cast;
use ZeroBoiler\DTO\Attributes\Confirmed;
use ZeroBoiler\DTO\Attributes\Date;
use ZeroBoiler\DTO\Attributes\DefaultValue;
use ZeroBoiler\DTO\Attributes\Declined;
use ZeroBoiler\DTO\Attributes\Different;
use ZeroBoiler\DTO\Attributes\Distinct;
use ZeroBoiler\DTO\Attributes\Email;
use ZeroBoiler\DTO\Attributes\EndsWith;
use ZeroBoiler\DTO\Attributes\Hidden;
use ZeroBoiler\DTO\Attributes\In;
use ZeroBoiler\DTO\Attributes\Integer;
use ZeroBoiler\DTO\Attributes\Json;
use ZeroBoiler\DTO\Attributes\MapFrom;
use ZeroBoiler\DTO\Attributes\Max;
use ZeroBoiler\DTO\Attributes\Min;
use ZeroBoiler\DTO\Attributes\Nullable;
use ZeroBoiler\DTO\Attributes\Numeric;
use ZeroBoiler\DTO\Attributes\Pattern;
use ZeroBoiler\DTO\Attributes\Present;
use ZeroBoiler\DTO\Attributes\Prohibited;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\Attributes\RequiredIf;
use ZeroBoiler\DTO\Attributes\RequiredUnless;
use ZeroBoiler\DTO\Attributes\RequiredWith;
use ZeroBoiler\DTO\Attributes\RequiredWithout;
use ZeroBoiler\DTO\Attributes\Same;
use ZeroBoiler\DTO\Attributes\Size;
use ZeroBoiler\DTO\Attributes\Sometimes;
use ZeroBoiler\DTO\Attributes\StartsWith;
use ZeroBoiler\DTO\Attributes\Url;
use ZeroBoiler\DTO\Attributes\Uuid;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;

describe('DTO attribute ruleKey consistency', function () {
    $attributes = [
        Accepted::class => 'accepted',
        Boolean::class => 'boolean',
        Declined::class => 'declined',
        Confirmed::class => 'confirmed',
        Distinct::class => 'distinct',
        Email::class => 'email',
        Integer::class => 'integer',
        Numeric::class => 'numeric',
        Url::class => 'url',
        Uuid::class => 'uuid',
        Json::class => 'json',
        Present::class => 'present',
        Prohibited::class => 'prohibited',
        Nullable::class => 'nullable',
        Sometimes::class => 'sometimes',
        Pattern::class => 'regex',
        StartsWith::class => 'starts_with',
        EndsWith::class => 'ends_with',
        Same::class => 'same',
        Different::class => 'different',
        RequiredIf::class => 'required_if',
        RequiredUnless::class => 'required_unless',
        RequiredWith::class => 'required_with',
        RequiredWithout::class => 'required_without',
        Size::class => 'size',
    ];

    foreach ($attributes as $attrClass => $expectedKey) {
        it("{$attrClass} has ruleKey '{$expectedKey}'", function () use ($attrClass, $expectedKey) {
            $attr = new $attrClass();
            expect($attr->ruleKey())->toBe($expectedKey);
        });
    }
});

describe('DtoCollection fluent operations', function () {
    it('chains push calls fluently', function () {
        $collection = DtoCollection::make();
        $d1 = DeepFixtureDTO::fromArray(['name' => 'A'], validate: false);
        $d2 = DeepFixtureDTO::fromArray(['name' => 'B'], validate: false);
        $d3 = DeepFixtureDTO::fromArray(['name' => 'C'], validate: false);

        $result = $collection->push($d1)->push($d2)->push($d3);

        expect($result)->toBe($collection);
        expect($collection->count())->toBe(3);
        expect($collection->last()->name)->toBe('C');
    });

    it('filter returns new collection with correct count', function () {
        $d1 = DeepFixtureDTO::fromArray(['name' => 'Alice'], validate: false);
        $d2 = DeepFixtureDTO::fromArray(['name' => 'Bob'], validate: false);
        $d3 = DeepFixtureDTO::fromArray(['name' => 'Alice'], validate: false);

        $collection = new DtoCollection([$d1, $d2, $d3]);
        $filtered = $collection->filter(
            fn (DeepFixtureDTO $dto): bool => $dto->name === 'Alice'
        );

        expect($filtered)->not->toBe($collection);
        expect($filtered->count())->toBe(2);
        expect($filtered->first()->name)->toBe('Alice');
    });

    it('pluck returns correct field values', function () {
        $d1 = DeepFixtureDTO::fromArray(['name' => 'Alice', 'age' => 30], validate: false);
        $d2 = DeepFixtureDTO::fromArray(['name' => 'Bob', 'age' => 25], validate: false);

        $collection = new DtoCollection([$d1, $d2]);

        expect($collection->pluck('name'))->toBe(['Alice', 'Bob']);
        expect($collection->pluck('age'))->toBe([30, 25]);
    });

    it('pluckKey returns key-value map', function () {
        $d1 = DeepFixtureDTO::fromArray(['name' => 'a@example.com', 'age' => 30], validate: false);
        $d2 = DeepFixtureDTO::fromArray(['name' => 'b@example.com', 'age' => 25], validate: false);

        $collection = new DtoCollection([$d1, $d2]);

        $map = $collection->pluckKey('name', 'age');
        expect($map)->toBe([
            'a@example.com' => 30,
            'b@example.com' => 25,
        ]);
    });

    it('pluckKey with single key returns full DTO arrays as values', function () {
        $d1 = DeepFixtureDTO::fromArray(['name' => 'Alice', 'age' => 30], validate: false);

        $collection = new DtoCollection([$d1]);
        $map = $collection->pluckKey('name');

        expect($map)->toHaveKey('Alice');
        expect($map['Alice'])->toBe(['name' => 'Alice', 'age' => 30]);
    });

    it('map returns plain array with correct types', function () {
        $d1 = DeepFixtureDTO::fromArray(['name' => 'Alice', 'age' => 30], validate: false);
        $d2 = DeepFixtureDTO::fromArray(['name' => 'Bob', 'age' => 25], validate: false);

        $collection = new DtoCollection([$d1, $d2]);

        $names = $collection->map(fn (DeepFixtureDTO $dto): string => $dto->name);

        expect($names)->toBe(['Alice', 'Bob']);
        // map receives index as second argument
        $ages = $collection->map(fn (DeepFixtureDTO $dto, int $index): int => $dto->age + $index);

        expect($ages)->toBe([30, 26]); // 30+0, 25+1
    });
});

describe('DTO equals() edge cases', function () {
    it('returns true for identical values', function () {
        $a = DeepFixtureDTO::fromArray(['name' => 'Alice', 'age' => 30], validate: false);
        $b = DeepFixtureDTO::fromArray(['name' => 'Alice', 'age' => 30], validate: false);

        expect($a->equals($b))->toBeTrue();
    });

    it('returns false for different values', function () {
        $a = DeepFixtureDTO::fromArray(['name' => 'Alice', 'age' => 30], validate: false);
        $b = DeepFixtureDTO::fromArray(['name' => 'Bob', 'age' => 30], validate: false);

        expect($a->equals($b))->toBeFalse();
    });

    it('compares including hidden fields for same hidden state', function () {
        $a = DeepFixtureHiddenDTO::fromArray(['name' => 'A', 'secret' => 'x'], validate: false);
        $b = DeepFixtureHiddenDTO::fromArray(['name' => 'A', 'secret' => 'x'], validate: false);

        // equals() uses toArray() which excludes hidden
        expect($a->equals($b))->toBeTrue();
    });

    it('equals ignores hidden field differences since toArray excludes them', function () {
        $a = DeepFixtureHiddenDTO::fromArray(['name' => 'A', 'secret' => 'x'], validate: false);
        $b = DeepFixtureHiddenDTO::fromArray(['name' => 'A', 'secret' => 'y'], validate: false);

        // Both toArray() exclude 'secret', so they're equal
        expect($a->equals($b))->toBeTrue();
    });
});

describe('DTO fromArray with MapFrom dot notation', function () {
    it('maps nested dot-notation keys', function () {
        $dto = MapFromDotDTO::fromArray([
            'user' => ['name' => 'Alice'],
            'user_age' => 30,
        ], validate: false);

        expect($dto->name)->toBe('Alice');
        expect($dto->age)->toBe(30);
    });
});

describe('DTO with ArrayCast edge cases', function () {
    it('casts JSON string to array', function () {
        $dto = ArrayCastEdgeDTO::fromArray([
            'tags' => '{"a":1,"b":2}',
        ], validate: false);

        expect($dto->tags)->toBe(['a' => 1, 'b' => 2]);
    });

    it('passes array through unchanged', function () {
        $dto = ArrayCastEdgeDTO::fromArray([
            'tags' => ['x', 'y'],
        ], validate: false);

        expect($dto->tags)->toBe(['x', 'y']);
    });

    it('handles empty string as empty array', function () {
        $dto = ArrayCastEdgeDTO::fromArray([
            'tags' => '',
        ], validate: false);

        expect($dto->tags)->toBe([]);
    });
});

describe('DTOException factory methods', function () {
    it('creates invalidCast exception', function () {
        $ex = DTOException::invalidCast('price', 'int', 'not-a-number');

        expect($ex->getMessage())->toContain('price');
        expect($ex->getMessage())->toContain('int');
        expect($ex->getMessage())->toContain('string');
    });

    it('creates invalidJson exception', function () {
        $ex = DTOException::invalidJson('metadata', 'Syntax error');

        expect($ex->getMessage())->toContain('metadata');
        expect($ex->getMessage())->toContain('Syntax error');
    });
});

describe('DTO with multiple validation attributes', function () {
    it('validates compound rules', function () {
        $dto = CompoundRuleDTO::fromArray([
            'username' => 'alice',
            'email' => 'alice@example.com',
            'age' => 25,
            'bio' => 'Hello world',
        ]);

        expect($dto->username)->toBe('alice');
        expect($dto->email)->toBe('alice@example.com');
        expect($dto->age)->toBe(25);
    });

    it('fails validation when compound rules are violated', function () {
        CompoundRuleDTO::fromArray([
            'username' => 'a',  // min:3
            'email' => 'not-an-email',
            'age' => 200,  // max:150
            'bio' => str_repeat('x', 600),  // max:500
        ]);
    })->throws(ValidationException::class);

    it('validates pattern rule', function () {
        $dto = CompoundRuleDTO::fromArray([
            'username' => 'alice123',
            'email' => 'alice@example.com',
            'age' => 25,
            'bio' => 'Hello',
        ]);

        expect($dto->username)->toBe('alice123');
    });

    it('fails pattern rule on invalid input', function () {
        CompoundRuleDTO::fromArray([
            'username' => 'alice!',  // contains special char, pattern: /^[a-zA-Z0-9]+$/
            'email' => 'test@example.com',
            'age' => 25,
            'bio' => 'Hello',
        ]);
    })->throws(ValidationException::class);
});

// ─── Test Fixtures ───────────────────────────────────────────────

class DeepFixtureDTO extends \ZeroBoiler\DTO\DataTransferObject
{
    public function __construct(
        public readonly string $name = '',
        public readonly int $age = 0,
    ) {}
}

class DeepFixtureHiddenDTO extends \ZeroBoiler\DTO\DataTransferObject
{
    public function __construct(
        public readonly string $name = '',
        #[Hidden]
        public readonly string $secret = '',
    ) {}
}

class MapFromDotDTO extends \ZeroBoiler\DTO\DataTransferObject
{
    public function __construct(
        #[MapFrom('user.name')]
        public readonly string $name = '',

        #[MapFrom('user_age')]
        public readonly int $age = 0,
    ) {}
}

class ArrayCastEdgeDTO extends \ZeroBoiler\DTO\DataTransferObject
{
    public function __construct(
        #[Cast('array')]
        public readonly array $tags = [],
    ) {}
}

class CompoundRuleDTO extends \ZeroBoiler\DTO\DataTransferObject
{
    public function __construct(
        #[Required, Min(3), Max(50), Pattern('/^[a-zA-Z0-9]+$/')]
        public readonly string $username,

        #[Required, Email, Max(255)]
        public readonly string $email,

        #[Required, Integer, Min(1), Max(150)]
        public readonly int $age,

        #[Max(500)]
        public readonly string $bio = '',
    ) {}
}
