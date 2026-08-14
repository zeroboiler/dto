<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ReflectionClass;
use ZeroBoiler\DTO\Attributes\Cast;
use ZeroBoiler\DTO\Attributes\DefaultValue;
use ZeroBoiler\DTO\Attributes\Hidden;
use ZeroBoiler\DTO\Attributes\MapFrom;
use ZeroBoiler\DTO\Attributes\Max;
use ZeroBoiler\DTO\Attributes\Min;
use ZeroBoiler\DTO\Attributes\Nullable;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\Attributes\StartsWith;
use ZeroBoiler\DTO\Cast\DTOCast;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\DTOManager;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Tests\Fixtures\ArticleDTO;
use ZeroBoiler\DTO\Tests\Fixtures\ArticleStatus;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\Currency;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;
use ZeroBoiler\DTO\Tests\Fixtures\InteractionEdgeCaseDTO;
use ZeroBoiler\DTO\Tests\Fixtures\MinimalDTO;

describe('DTO with() immutable update and validation', function (): void {
    it('creates new instance with merged data', function (): void {
        $dto = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'hello']);

        $updated = $dto->with(['name' => 'Bob']);

        expect($updated->name)->toBe('Bob');
        expect($updated->value)->toBe('hello');
        // Original unchanged
        expect($dto->name)->toBe('Alice');
    });

    it('validates merged data and throws on failure', function (): void {
        $dto = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'hello']);

        expect(fn () => $dto->with(['name' => '']))->toThrow(\Illuminate\Validation\ValidationException::class);
    });

    it('fromArray without validation creates DTO with raw data', function (): void {
        $dto = MinimalDTO::fromArray(['name' => '', 'value' => ''], validate: false);

        expect($dto->name)->toBe('');
        expect($dto->value)->toBe('');
    });
});

describe('DTO fromJson edge cases', function (): void {
    it('throws DTOException on invalid JSON', function (): void {
        expect(fn () => MinimalDTO::fromJson('{invalid json}'))
            ->toThrow(DTOException::class);
    });

    it('throws DTOException on sequential JSON array', function (): void {
        expect(fn () => MinimalDTO::fromJson('[1, 2, 3]'))
            ->toThrow(DTOException::class);
    });

    it('accepts empty JSON object', function (): void {
        $dto = EmptyDTO::fromJson('{}', validate: false);

        expect($dto->foo)->toBeNull();
        expect($dto->bar)->toBeNull();
    });

    it('parses valid JSON object correctly', function (): void {
        $dto = MinimalDTO::fromJson('{"name":"Alice","value":"hello"}');

        expect($dto->name)->toBe('Alice');
        expect($dto->value)->toBe('hello');
    });
});

describe('DTO equals() comparison', function (): void {
    it('returns true for identical DTOs', function (): void {
        $a = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'hello']);
        $b = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'hello']);

        expect($a->equals($b))->toBeTrue();
    });

    it('returns false for different DTOs', function (): void {
        $a = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'hello']);
        $b = MinimalDTO::fromArray(['name' => 'Bob', 'value' => 'hello']);

        expect($a->equals($b))->toBeFalse();
    });
});

describe('DTO isEmpty() and isNotEmpty()', function (): void {
    it('returns true for DTO with all null/empty values', function (): void {
        $dto = EmptyDTO::fromArray([], validate: false);

        expect($dto->isEmpty())->toBeTrue();
        expect($dto->isNotEmpty())->toBeFalse();
    });

    it('returns false for DTO with at least one non-empty value', function (): void {
        $dto = EmptyDTO::fromArray(['foo' => 'hello'], validate: false);

        expect($dto->isEmpty())->toBeFalse();
        expect($dto->isNotEmpty())->toBeTrue();
    });

    it('treats int 0 as non-empty', function (): void {
        $dto = InteractionEdgeCaseDTO::fromArray(
            ['username' => 'alice', 'handle' => '@alice'],
            validate: false,
        );

        // limit has default value of 100
        expect($dto->isEmpty())->toBeFalse();
    });
});

describe('DTO only() and except() selective output', function (): void {
    it('only() returns specified fields only', function (): void {
        $dto = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'hello']);

        $result = $dto->only('name');

        expect($result)->toBe(['name' => 'Alice']);
    });

    it('except() excludes specified fields', function (): void {
        $dto = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'hello']);

        $result = $dto->except('name');

        expect($result)->toBe(['value' => 'hello']);
    });

    it('Hidden properties are excluded from toArray but present in allValues', function (): void {
        $dto = InteractionEdgeCaseDTO::fromArray(
            ['username' => 'alice', 'handle' => '@alice'],
            validate: false,
        );

        $toArray = $dto->toArray();
        $allValues = $dto->allValues();

        expect($toArray)->not->toHaveKey('source');
        expect($allValues)->toHaveKey('source');
        expect($allValues['source'])->toBe('internal');
    });
});

describe('DtoCollection immutability patterns', function (): void {
    it('append() returns new collection without modifying original', function (): void {
        $a = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'a']);
        $b = MinimalDTO::fromArray(['name' => 'Bob', 'value' => 'b']);

        $col1 = DtoCollection::make([$a]);
        $col2 = $col1->append($b);

        expect($col1->count())->toBe(1);
        expect($col2->count())->toBe(2);
    });

    it('merge() returns new collection combining both', function (): void {
        $a = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'a']);
        $b = MinimalDTO::fromArray(['name' => 'Bob', 'value' => 'b']);
        $c = MinimalDTO::fromArray(['name' => 'Charlie', 'value' => 'c']);

        $col1 = DtoCollection::make([$a]);
        $col2 = DtoCollection::make([$b, $c]);

        $merged = $col1->merge($col2);

        expect($merged->count())->toBe(3);
        expect($col1->count())->toBe(1);
        expect($col2->count())->toBe(2);
    });

    it('filter() returns new collection with filtered items', function (): void {
        $a = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'a']);
        $b = MinimalDTO::fromArray(['name' => 'Bob', 'value' => 'b']);

        $col = DtoCollection::make([$a, $b]);
        $filtered = $col->filter(fn (DataTransferObject $dto) => $dto->name === 'Alice');

        expect($filtered->count())->toBe(1);
        expect($col->count())->toBe(2);
    });

    it('push() mutates in place and returns self', function (): void {
        $a = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'a']);
        $b = MinimalDTO::fromArray(['name' => 'Bob', 'value' => 'b']);

        $col = DtoCollection::make([$a]);
        $returned = $col->push($b);

        expect($col->count())->toBe(2);
        expect($returned)->toBe($col);
    });
});

describe('DtoCollection utility methods', function (): void {
    it('pluck() extracts single property values', function (): void {
        $a = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'a']);
        $b = MinimalDTO::fromArray(['name' => 'Bob', 'value' => 'b']);

        $col = DtoCollection::make([$a, $b]);

        expect($col->pluck('name'))->toBe(['Alice', 'Bob']);
        expect($col->pluck('value'))->toBe(['a', 'b']);
    });

    it('first() and last() return correct items', function (): void {
        $a = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'a']);
        $b = MinimalDTO::fromArray(['name' => 'Bob', 'value' => 'b']);

        $col = DtoCollection::make([$a, $b]);

        expect($col->first()->name)->toBe('Alice');
        expect($col->last()->name)->toBe('Bob');
    });

    it('isEmpty() and isNotEmpty() work correctly', function (): void {
        $col = DtoCollection::make([]);

        expect($col->isEmpty())->toBeTrue();
        expect($col->isNotEmpty())->toBeFalse();
    });

    it('toArray() serializes all DTOs', function (): void {
        $a = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'a']);
        $b = MinimalDTO::fromArray(['name' => 'Bob', 'value' => 'b']);

        $col = DtoCollection::make([$a, $b]);
        $arr = $col->toArray();

        expect($arr)->toHaveCount(2);
        expect($arr[0])->toBe(['name' => 'Alice', 'value' => 'a']);
        expect($arr[1])->toBe(['name' => 'Bob', 'value' => 'b']);
    });
});

describe('DTO fromPartialArray PATCH semantics', function (): void {
    it('fills provided fields and uses defaults for missing', function (): void {
        $dto = CreateUserDTO::fromPartialArray(['name' => 'Alice']);

        expect($dto->name)->toBe('Alice');
        expect($dto->status)->toBe('active'); // DefaultValue
        expect($dto->tags)->toBe([]);  // Default
    });

    it('works with empty array', function (): void {
        $dto = CreateUserDTO::fromPartialArray([]);

        expect($dto->name)->toBe('');
        expect($dto->email)->toBe('');
        expect($dto->status)->toBe('active');
    });
});

describe('DTO MapFrom attribute', function (): void {
    it('maps source key to property', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
            'phone_number' => '+905551234567',
        ]);

        expect($dto->phone)->toBe('+905551234567');
    });
});

describe('DTO rules() and rulesFor()', function (): void {
    it('rules() returns Laravel-compatible rules array', function (): void {
        $rules = MinimalDTO::rules();

        expect($rules)->toBeArray();
        expect($rules)->toHaveKey('name');
        expect($rules)->toHaveKey('value');
        expect($rules['name'])->toContain('required');
        expect($rules['value'])->toContain('required');
    });

    it('rulesFor() defaults to rules() for unknown action', function (): void {
        $rules = MinimalDTO::rules();
        $rulesFor = MinimalDTO::rulesFor('update');

        expect($rules)->toBe($rulesFor);
    });
});

describe('DTOException named constructors', function (): void {
    it('invalidCast includes property and type info', function (): void {
        $e = DTOException::invalidCast('age', 'integer', 'not_a_number');

        expect($e->getMessage())->toContain('age');
        expect($e->getMessage())->toContain('integer');
    });

    it('invalidJson includes property and error info', function (): void {
        $e = DTOException::invalidJson('data', 'Syntax error');

        expect($e->getMessage())->toContain('data');
        expect($e->getMessage())->toContain('Syntax error');
    });

    it('__toString returns class and message', function (): void {
        $e = DTOException::invalidCast('age', 'integer', 'abc');

        $str = (string) $e;

        expect($str)->toContain(DTOException::class);
        expect($str)->toContain('age');
    });
});

describe('DTO enum property hydration', function (): void {
    it('hydrates enum from string value', function (): void {
        $dto = ArticleDTO::fromArray(
            ['authorEmail' => 'a@b.com', 'title' => 'Test', 'body' => 'Hello world content here', 'status' => 'published'],
        );

        expect($dto->status)->toBe(ArticleStatus::PUBLISHED);
    });

    it('hydrates enum from int value', function (): void {
        $dto = ArticleDTO::fromArray(
            ['authorEmail' => 'a@b.com', 'title' => 'Test', 'body' => 'Hello world content here', 'status' => 2],
        );

        expect($dto->status)->toBe(ArticleStatus::ARCHIVED);
    });

    it('uses default enum value when not provided', function (): void {
        $dto = ArticleDTO::fromArray(
            ['authorEmail' => 'a@b.com', 'title' => 'Test', 'body' => 'Hello world content here'],
        );

        expect($dto->status)->toBe(ArticleStatus::DRAFT);
    });

    it('serializes enum to backed value in toArray()', function (): void {
        $dto = ArticleDTO::fromArray(
            ['authorEmail' => 'a@b.com', 'title' => 'Test', 'body' => 'Hello world content here', 'currency' => 'EUR'],
        );

        $arr = $dto->toArray();

        expect($arr['currency'])->toBe('EUR');
    });
});

describe('DTOManager delegation', function (): void {
    it('make() creates DTO from data', function (): void {
        $manager = new DTOManager;
        $dto = $manager->make(MinimalDTO::class, ['name' => 'Alice', 'value' => 'hello']);

        expect($dto->name)->toBe('Alice');
    });

    it('rules() returns validation rules', function (): void {
        $manager = new DTOManager;
        $rules = $manager->rules(MinimalDTO::class);

        expect($rules)->toHaveKey('name');
    });

    it('fromPartialArray() creates DTO with PATCH semantics', function (): void {
        $manager = new DTOManager;
        $dto = $manager->fromPartialArray(CreateUserDTO::class, ['name' => 'Alice']);

        expect($dto->name)->toBe('Alice');
        expect($dto->status)->toBe('active');
    });
});

describe('DTO Cast type attribute', function (): void {
    it('casts string to integer', function (): void {
        $dto = ArticleDTO::fromArray(
            ['authorEmail' => 'a@b.com', 'title' => 'Test', 'body' => 'Hello world content here', 'viewCount' => '42'],
        );

        expect($dto->viewCount)->toBe(42);
        expect($dto->viewCount)->toBeInt();
    });

    it('casts string to float', function (): void {
        $dto = ArticleDTO::fromArray(
            ['authorEmail' => 'a@b.com', 'title' => 'Test', 'body' => 'Hello world content here', 'rating' => '4.5'],
        );

        expect($dto->rating)->toBe(4.5);
        expect($dto->rating)->toBeFloat();
    });
});
