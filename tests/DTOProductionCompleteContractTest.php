<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\Attributes\Accepted;
use ZeroBoiler\DTO\Attributes\Boolean;
use ZeroBoiler\DTO\Attributes\Cast;
use ZeroBoiler\DTO\Attributes\Confirmed;
use ZeroBoiler\DTO\Attributes\Date;
use ZeroBoiler\DTO\Attributes\Declined;
use ZeroBoiler\DTO\Attributes\DefaultValue;
use ZeroBoiler\DTO\Attributes\Different;
use ZeroBoiler\DTO\Attributes\Distinct;
use ZeroBoiler\DTO\Attributes\Email;
use ZeroBoiler\DTO\Attributes\EndsWith;
use ZeroBoiler\DTO\Attributes\Enum as EnumAttr;
use ZeroBoiler\DTO\Attributes\Hidden;
use ZeroBoiler\DTO\Attributes\In;
use ZeroBoiler\DTO\Attributes\Integer;
use ZeroBoiler\DTO\Attributes\Json;
use ZeroBoiler\DTO\Attributes\MapFrom;
use ZeroBoiler\DTO\Attributes\Max;
use ZeroBoiler\DTO\Attributes\Min;
use ZeroBoiler\DTO\Attributes\NestedArray;
use ZeroBoiler\DTO\Attributes\Nullable;
use ZeroBoiler\DTO\Attributes\Numeric;
use ZeroBoiler\DTO\Attributes\Pattern;
use ZeroBoiler\DTO\Attributes\Present;
use ZeroBoiler\DTO\Attributes\Prohibited;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\Attributes\RequiredIf;
use ZeroBoiler\DTO\Attributes\RequiredUnless;
use ZeroBoiler\DTO\Attributes\RequiredWith;
use ZeroBoiler\DTO\Attributes\RequiredWithAll;
use ZeroBoiler\DTO\Attributes\RequiredWithout;
use ZeroBoiler\DTO\Attributes\RequiredWithoutAll;
use ZeroBoiler\DTO\Attributes\Same;
use ZeroBoiler\DTO\Attributes\Size;
use ZeroBoiler\DTO\Attributes\Sometimes;
use ZeroBoiler\DTO\Attributes\StartsWith;
use ZeroBoiler\DTO\Attributes\Url;
use ZeroBoiler\DTO\Attributes\Uuid;
use ZeroBoiler\DTO\Contracts\ValidationAttribute;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;
use ZeroBoiler\DTO\Tests\Fixtures\MinimalDTO;
use ZeroBoiler\DTO\Tests\Fixtures\NoConstructorDTO;
use ZeroBoiler\DTO\Tests\Fixtures\ScalarConstraintsDTO;
use ZeroBoiler\DTO\Tests\Fixtures\ValidationTestDTO;

describe('Attribute final and readonly contract', function (): void {
    $validationAttributes = [
        Accepted::class,
        Boolean::class,
        Confirmed::class,
        Date::class,
        Declined::class,
        Different::class,
        Distinct::class,
        Email::class,
        EndsWith::class,
        EnumAttr::class,
        Hidden::class,
        In::class,
        Integer::class,
        Json::class,
        Max::class,
        Min::class,
        Nullable::class,
        Numeric::class,
        Pattern::class,
        Present::class,
        Prohibited::class,
        Required::class,
        RequiredIf::class,
        RequiredUnless::class,
        RequiredWith::class,
        RequiredWithAll::class,
        RequiredWithout::class,
        RequiredWithoutAll::class,
        Same::class,
        Size::class,
        Sometimes::class,
        StartsWith::class,
        Url::class,
        Uuid::class,
    ];

    foreach ($validationAttributes as $attrClass) {
        it("{$attrClass} is final", function () use ($attrClass): void {
            $ref = new ReflectionClass($attrClass);
            expect($ref->isFinal())->toBeTrue();
        });

        it("{$attrClass} implements ValidationAttribute", function () use ($attrClass): void {
            expect(is_a($attrClass, ValidationAttribute::class, true))->toBeTrue();
        });
    }

    // Metadata attributes (not validation, but still final)
    $metaAttributes = [
        Cast::class,
        DefaultValue::class,
        MapFrom::class,
    ];

    foreach ($metaAttributes as $attrClass) {
        it("{$attrClass} is final", function () use ($attrClass): void {
            $ref = new ReflectionClass($attrClass);
            expect($ref->isFinal())->toBeTrue();
        });
    }
});

describe('ValidationAttribute ruleKey() returns valid Laravel rule names', function (): void {
    $ruleKeyMap = [
        Accepted::class => 'accepted',
        Boolean::class => 'boolean',
        Confirmed::class => 'confirmed',
        Declined::class => 'declined',
        Different::class => 'different',
        Distinct::class => 'distinct',
        Email::class => 'email',
        EndsWith::class => 'ends_with',
        Integer::class => 'integer',
        Json::class => 'json',
        Max::class => 'max',
        Min::class => 'min',
        Nullable::class => 'nullable',
        Numeric::class => 'numeric',
        Pattern::class => 'regex',
        Present::class => 'present',
        Prohibited::class => 'prohibited',
        Required::class => 'required',
        RequiredIf::class => 'required_if',
        RequiredUnless::class => 'required_unless',
        RequiredWith::class => 'required_with',
        RequiredWithAll::class => 'required_with_all',
        RequiredWithout::class => 'required_without',
        RequiredWithoutAll::class => 'required_without_all',
        Same::class => 'same',
        Size::class => 'size',
        Sometimes::class => 'sometimes',
        StartsWith::class => 'starts_with',
        Url::class => 'url',
        Uuid::class => 'uuid',
        In::class => 'in',
        Hidden::class => 'hidden',
    ];

    foreach ($ruleKeyMap as $attrClass => $expectedKey) {
        it("{$attrClass}::ruleKey() returns '{$expectedKey}'", function () use ($attrClass, $expectedKey): void {
            $instance = new $attrClass();
            expect($instance->ruleKey())->toBe($expectedKey);
        });
    }
});

describe('DTOException factory methods', function (): void {
    it('creates invalidCast with correct message', function (): void {
        $e = DTOException::invalidCast('email', 'integer', 'not-an-int');
        expect($e->getMessage())->toContain('email');
        expect($e->getMessage())->toContain('integer');
    });

    it('creates invalidJson with correct message', function (): void {
        $e = DTOException::invalidJson('data', 'Syntax error');
        expect($e->getMessage())->toContain('data');
        expect($e->getMessage())->toContain('Syntax error');
    });
});

describe('DtoCollection type safety', function (): void {
    it('rejects non-DTO items in constructor', function (): void {
        expect(fn () => new DtoCollection(['not a dto']))->toThrow(\InvalidArgumentException::class);
    });

    it('accepts DTO instances in constructor', function (): void {
        // EmptyDTO has no constructor args, safe to construct
        $dto = EmptyDTO::fromArray([]);
        $col = new DtoCollection([$dto]);
        expect($col->count())->toBe(1);
    });

    it('make() creates collection from DTOs', function (): void {
        $dtoArray = [
            EmptyDTO::fromArray([]),
            EmptyDTO::fromArray([]),
        ];
        $col = DtoCollection::make($dtoArray);
        expect($col->count())->toBe(2);
    });

    it('push() mutates in place and returns self', function (): void {
        $col = new DtoCollection([EmptyDTO::fromArray([])]);
        $returned = $col->push(EmptyDTO::fromArray([]));
        expect($col->count())->toBe(2);
        expect($returned)->toBe($col);
    });

    it('append() returns new collection without mutating original', function (): void {
        $col = new DtoCollection([EmptyDTO::fromArray([])]);
        $newCol = $col->append(EmptyDTO::fromArray([]));
        expect($col->count())->toBe(1);
        expect($newCol->count())->toBe(2);
    });

    it('merge() combines two collections immutably', function (): void {
        $col1 = new DtoCollection([EmptyDTO::fromArray([])]);
        $col2 = new DtoCollection([EmptyDTO::fromArray([]), EmptyDTO::fromArray([])]);
        $merged = $col1->merge($col2);
        expect($col1->count())->toBe(1);
        expect($col2->count())->toBe(2);
        expect($merged->count())->toBe(3);
    });

    it('filter() returns new DtoCollection', function (): void {
        $dtoArray = [
            EmptyDTO::fromArray([]),
            EmptyDTO::fromArray([]),
        ];
        $col = new DtoCollection($dtoArray);
        $filtered = $col->filter(fn (DataTransferObject $dto): bool => true);
        expect($filtered)->toBeInstanceOf(DtoCollection::class);
        expect($filtered->count())->toBe(2);
    });

    it('offsetUnset re-indexes array', function (): void {
        $col = new DtoCollection([
            EmptyDTO::fromArray([]),
            EmptyDTO::fromArray([]),
            EmptyDTO::fromArray([]),
        ]);
        unset($col[0]);
        expect($col->count())->toBe(2);
        // After re-index, keys should be 0, 1
        expect(array_keys($col->items()))->toBe([0, 1]);
    });

    it('isEmpty() and isNotEmpty() work correctly', function (): void {
        $empty = new DtoCollection();
        expect($empty->isEmpty())->toBeTrue();
        expect($empty->isNotEmpty())->toBeFalse();

        $nonEmpty = new DtoCollection([EmptyDTO::fromArray([])]);
        expect($nonEmpty->isEmpty())->toBeFalse();
        expect($nonEmpty->isNotEmpty())->toBeTrue();
    });

    it('first() and last() return correct items', function (): void {
        $col = new DtoCollection([EmptyDTO::fromArray([]), EmptyDTO::fromArray([]), EmptyDTO::fromArray([])]);
        $first = $col->first();
        $last = $col->last();
        expect($first)->toBeInstanceOf(EmptyDTO::class);
        expect($last)->toBeInstanceOf(EmptyDTO::class);
        expect($first)->not->toBe($last);
    });

    it('map() returns plain array', function (): void {
        $col = new DtoCollection([EmptyDTO::fromArray([]), EmptyDTO::fromArray([])]);
        $result = $col->map(fn (DataTransferObject $dto): int => 1);
        expect($result)->toBe([1, 1]);
    });

    it('toArray() serializes all DTOs', function (): void {
        $col = new DtoCollection([EmptyDTO::fromArray([])]);
        $arr = $col->toArray();
        expect($arr)->toBeArray();
        expect(count($arr))->toBe(1);
    });

    it('jsonSerialize() returns toArray() output', function (): void {
        $col = new DtoCollection([EmptyDTO::fromArray([])]);
        expect($col->jsonSerialize())->toBe($col->toArray());
    });

    it('allValues() includes hidden fields', function (): void {
        $col = new DtoCollection([EmptyDTO::fromArray([])]);
        $all = $col->allValues();
        expect($all)->toBeArray();
    });
});

describe('DataTransferObject abstract contract', function (): void {
    it('NoConstructorDTO has empty rules', function (): void {
        $rules = NoConstructorDTO::rules();
        expect($rules)->toBe([]);
    });

    it('NoConstructorDTO validates empty data', function (): void {
        $result = NoConstructorDTO::validateArray([]);
        expect($result)->toBe([]);
    });

    it('NoConstructorDTO fromArray returns instance', function (): void {
        $dto = NoConstructorDTO::fromArray([]);
        expect($dto)->toBeInstanceOf(NoConstructorDTO::class);
    });

    it('NoConstructorDTO toArray returns empty array', function (): void {
        $dto = NoConstructorDTO::fromArray([]);
        expect($dto->toArray())->toBe([]);
    });

    it('rulesFor returns same as rules by default', function (): void {
        $rules = NoConstructorDTO::rules();
        $rulesForCreate = NoConstructorDTO::rulesFor('create');
        expect($rulesForCreate)->toBe($rules);
    });
});

describe('Validation rules generation', function (): void {
    it('generates required rule for required fields', function (): void {
        $rules = ValidationTestDTO::rules();
        $nameRules = $rules['name'] ?? [];
        expect($nameRules)->toContain('required');
    });

    it('generates integer and between rules for int fields', function (): void {
        $rules = ValidationTestDTO::rules();
        $ageRules = $rules['age'] ?? [];
        expect($ageRules)->toContain('required');
        expect($ageRules)->toContain('integer');
        $hasBetween = count(array_filter($ageRules, fn (mixed $r): bool => is_string($r) && str_starts_with($r, 'between:'))) > 0;
        expect($hasBetween)->toBeTrue();
    });

    it('generates min/max rules for constrained fields', function (): void {
        $rules = ScalarConstraintsDTO::rules();
        $scoreRules = $rules['score'] ?? [];
        $hasMin = count(array_filter($scoreRules, fn (mixed $r): bool => is_string($r) && str_starts_with($r, 'min:'))) > 0;
        $hasMax = count(array_filter($scoreRules, fn (mixed $r): bool => is_string($r) && str_starts_with($r, 'max:'))) > 0;
        expect($hasMin)->toBeTrue();
        expect($hasMax)->toBeTrue();
    });

    it('generates boolean rule for bool fields', function (): void {
        $rules = ScalarConstraintsDTO::rules();
        $adminRules = $rules['is_admin'] ?? [];
        expect($adminRules)->toContain('boolean');
    });

    it('generates uuid rule for uuid fields', function (): void {
        $rules = ScalarConstraintsDTO::rules();
        $uuidRules = $rules['uuid'] ?? [];
        expect($uuidRules)->toContain('uuid');
    });
});

describe('Immutable update with()', function (): void {
    it('creates new instance with updated value', function (): void {
        $dto = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'test']);
        $updated = $dto->with(['name' => 'Bob']);
        expect($dto->toArray()['name'])->toBe('Alice');
        expect($updated->toArray()['name'])->toBe('Bob');
    });

    it('validates merged data', function (): void {
        $dto = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'test']);
        $updated = $dto->with(['name' => 'Bob']);
        expect($updated)->toBeInstanceOf(MinimalDTO::class);
    });
});

describe('Selective output only() and except()', function (): void {
    it('only() returns specified keys', function (): void {
        $dto = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'test']);
        $result = $dto->only('name');
        expect($result)->toHaveKey('name');
        expect(count($result))->toBe(1);
    });

    it('only() ignores non-existent keys', function (): void {
        $dto = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'test']);
        $result = $dto->only('name', 'nonexistent');
        expect($result)->toHaveKey('name');
        expect($result)->not->toHaveKey('nonexistent');
    });

    it('except() excludes specified keys', function (): void {
        $dto = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'test']);
        $result = $dto->except('name');
        expect($result)->not->toHaveKey('name');
    });

    it('accepts string key to only()', function (): void {
        $dto = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'test']);
        $result = $dto->only('name');
        expect($result)->toHaveCount(1);
    });
});

describe('equals() and isEmpty() / isNotEmpty()', function (): void {
    it('equals() compares toArray() output', function (): void {
        $dto1 = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'test']);
        $dto2 = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'test']);
        $dto3 = MinimalDTO::fromArray(['name' => 'Bob', 'value' => 'test']);
        expect($dto1->equals($dto2))->toBeTrue();
        expect($dto1->equals($dto3))->toBeFalse();
    });

    it('isEmpty() detects empty DTO', function (): void {
        $dto = EmptyDTO::fromArray([]);
        expect($dto->isEmpty())->toBeTrue();
        expect($dto->isNotEmpty())->toBeFalse();
    });
});

describe('toJson() encoding', function (): void {
    it('returns valid JSON string', function (): void {
        $dto = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'test']);
        $json = $dto->toJson();
        expect(json_validate($json))->toBeTrue();
    });

    it('jsonSerialize returns toArray()', function (): void {
        $dto = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'test']);
        expect($dto->jsonSerialize())->toBe($dto->toArray());
    });
});
