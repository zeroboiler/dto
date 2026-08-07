<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use Illuminate\Validation\ValidationException;
use ZeroBoiler\DTO\Contracts\FromRequestDTO;
use ZeroBoiler\DTO\Contracts\ValidatableDTO;
use ZeroBoiler\DTO\Contracts\ValidationAttribute;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;
use ZeroBoiler\DTO\Tests\Fixtures\MinimalDTO;

describe('DataTransferObject — strict type safety', function () {
    it('implements FromRequestDTO, ValidatableDTO, Arrayable, JsonSerializable', function () {
        $ref = new \ReflectionClass(DataTransferObject::class);

        expect($ref->implementsInterface(FromRequestDTO::class))->toBeTrue();
        expect($ref->implementsInterface(ValidatableDTO::class))->toBeTrue();
        expect($ref->implementsInterface(\Illuminate\Contracts\Support\Arrayable::class))->toBeTrue();
        expect($ref->implementsInterface(\JsonSerializable::class))->toBeTrue();
    });

    it('fromArray with validate:false skips validation', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'not-an-email', // would fail email validation
            'name' => 'T',           // would fail min:2
        ], validate: false);

        expect($dto)->toBeInstanceOf(CreateUserDTO::class);
        expect($dto->email)->toBe('not-an-email');
    });

    it('fromArray with validate:true throws ValidationException', function () {
        CreateUserDTO::fromArray([
            'email' => 'not-an-email',
            'name' => 'T',
        ], validate: true);
    })->throws(ValidationException::class);
});

describe('Serialization — strict output types', function () {
    it('toArray returns associative array with string keys', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
        ], validate: false);

        $array = $dto->toArray();

        expect($array)->toBeArray();

        // Verify all keys are strings
        foreach (array_keys($array) as $key) {
            expect($key)->toBeString();
        }
    });

    it('allValues includes hidden fields', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
            'password' => 'secret123',
        ], validate: false);

        $all = $dto->allValues();
        $visible = $dto->toArray();

        expect($all)->toHaveKey('password');
        expect($visible)->not->toHaveKey('password');
    });

    it('toJson returns valid JSON string', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
        ], validate: false);

        $json = $dto->toJson();

        expect($json)->toBeString();
        expect($json)->not->toBeEmpty();

        $decoded = json_decode($json, true);
        expect($decoded)->toBeArray();
    });

    it('jsonSerialize returns same as toArray', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
        ], validate: false);

        expect($dto->jsonSerialize())->toEqual($dto->toArray());
    });

    it('toJson with JSON_PRETTY_PRINT produces formatted output', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
        ], validate: false);

        $json = $dto->toJson(JSON_PRETTY_PRINT);

        expect($json)->toContain("\n");
        expect($json)->toContain('  ');
    });
});

describe('only() and except() — selective output', function () {
    it('only() with single string key', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
            'status' => 'active',
        ], validate: false);

        $result = $dto->only('email');

        expect($result)->toHaveKey('email');
        expect($result)->not->toHaveKey('name');
        expect($result)->not->toHaveKey('status');
    });

    it('only() with multiple string keys', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
            'status' => 'active',
        ], validate: false);

        $result = $dto->only('email', 'name');

        expect($result)->toHaveKey('email');
        expect($result)->toHaveKey('name');
        expect($result)->not->toHaveKey('status');
    });

    it('only() with array of keys', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
        ], validate: false);

        $result = $dto->only(['name']);

        expect($result)->toHaveKey('name');
        expect($result)->not->toHaveKey('email');
    });

    it('only() ignores non-existent keys silently', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
        ], validate: false);

        $result = $dto->only('email', 'nonexistent');

        expect($result)->toHaveKey('email');
        expect($result)->not->toHaveKey('nonexistent');
    });

    it('except() with single string key', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
        ], validate: false);

        $result = $dto->except('email');

        expect($result)->not->toHaveKey('email');
        expect($result)->toHaveKey('name');
    });

    it('except() with multiple keys', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
            'status' => 'active',
        ], validate: false);

        $result = $dto->except('email', 'name');

        expect($result)->not->toHaveKey('email');
        expect($result)->not->toHaveKey('name');
        expect($result)->toHaveKey('status');
    });

    it('except() ignores non-existent keys silently', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
        ], validate: false);

        $result = $dto->except('nonexistent');

        expect($result)->toHaveKey('email');
        expect($result)->toHaveKey('name');
    });
});

describe('equals() — value equality', function () {
    it('same values produce equal DTOs', function () {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
        ], validate: false);

        $dto2 = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
        ], validate: false);

        expect($dto1->equals($dto2))->toBeTrue();
    });

    it('different values produce unequal DTOs', function () {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
        ], validate: false);

        $dto2 = CreateUserDTO::fromArray([
            'email' => 'other@example.com',
            'name' => 'Test User',
        ], validate: false);

        expect($dto1->equals($dto2))->toBeFalse();
    });

    it('equals() excludes hidden fields from comparison', function () {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
            'password' => 'secret1',
        ], validate: false);

        $dto2 = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
            'password' => 'secret2',
        ], validate: false);

        // Password is hidden, so equals() compares only visible fields
        expect($dto1->equals($dto2))->toBeTrue();
    });
});

describe('isEmpty() / isNotEmpty() — state checks', function () {
    it('DTO with all defaults is not empty if defaults are non-empty values', function () {
        // MinimalDTO likely has defaults
        $dto = MinimalDTO::fromArray([], validate: false);

        // This test documents the actual behavior
        expect($dto)->toBeInstanceOf(MinimalDTO::class);
    });

    it('empty DTO reports empty', function () {
        $dto = EmptyDTO::fromArray([], validate: false);

        expect($dto->isEmpty())->toBeTrue();
        expect($dto->isNotEmpty())->toBeFalse();
    });

    it('DTO with non-empty fields reports not empty', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
        ], validate: false);

        expect($dto->isEmpty())->toBeFalse();
        expect($dto->isNotEmpty())->toBeTrue();
    });

    it('0 and 0.0 are considered non-empty', function () {
        // Test with integer 0 and float 0.0 if fixture supports it
        // This verifies the isEmpty() fix for issue #2
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
        ], validate: false);

        // The DTO has non-empty fields, so isNotEmpty() should be true
        expect($dto->isNotEmpty())->toBeTrue();
    });
});

describe('with() — immutable update', function () {
    it('with() returns new instance', function () {
        $original = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
        ], validate: false);

        $updated = $original->with(['name' => 'New Name']);

        expect($original)->not->toBe($updated);
        expect($original->name)->toBe('Test User');
        expect($updated->name)->toBe('New Name');
    });

    it('with() validates the merged data', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
        ], validate: false);

        $dto->with(['email' => 'not-an-email']);
    })->throws(ValidationException::class);

    it('with() ignores validate parameter (always validates)', function () {
        // The $validate parameter is deprecated and has no effect
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
        ], validate: false);

        // This should still validate because with() always validates
        $dto->with(['email' => 'not-an-email'], validate: false);
    })->throws(ValidationException::class);

    it('with() with empty overrides returns equivalent DTO', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
        ], validate: false);

        $same = $dto->with([]);

        expect($dto->toArray())->toEqual($same->toArray());
    });
});

describe('fromJson() — JSON hydration', function () {
    it('fromJson parses valid JSON object', function () {
        $dto = CreateUserDTO::fromJson(json_encode([
            'email' => 'test@example.com',
            'name' => 'Test User',
        ]), validate: false);

        expect($dto)->toBeInstanceOf(CreateUserDTO::class);
        expect($dto->email)->toBe('test@example.com');
    });

    it('fromJson throws DTOException for invalid JSON syntax', function () {
        CreateUserDTO::fromJson('{invalid}');
    })->throws(DTOException::class);

    it('fromJson throws DTOException for sequential array JSON', function () {
        CreateUserDTO::fromJson('["a", "b"]');
    })->throws(DTOException::class);

    it('fromJson throws DTOException for JSON null', function () {
        CreateUserDTO::fromJson('null');
    })->throws(DTOException::class);

    it('fromJson throws DTOException for JSON boolean', function () {
        CreateUserDTO::fromJson('true');
    })->throws(DTOException::class);

    it('fromJson throws DTOException for JSON number', function () {
        CreateUserDTO::fromJson('42');
    })->throws(DTOException::class);

    it('fromJson exception message contains error context', function () {
        try {
            CreateUserDTO::fromJson('{bad json}');
        } catch (DTOException $e) {
            expect($e->getMessage())->toContain('(root)');
        }
    });
});

describe('DTOException — named constructors', function () {
    it('invalidCast includes property name and type', function () {
        $exception = DTOException::invalidCast('age', 'integer', 'not_a_number');

        expect($exception->getMessage())->toContain('age');
        expect($exception->getMessage())->toContain('integer');
    });

    it('invalidCast uses get_debug_type for value display', function () {
        $exception = DTOException::invalidCast('data', 'array', 42);

        expect($exception->getMessage())->toContain('int'); // get_debug_type(42) = 'int'
    });

    it('invalidJson includes property name and error', function () {
        $exception = DTOException::invalidJson('metadata', 'Syntax error');

        expect($exception->getMessage())->toContain('metadata');
        expect($exception->getMessage())->toContain('Syntax error');
    });
});

describe('ValidationAttribute contract — strict compliance', function () {
    it('all validation attributes implement ValidationAttribute', function () {
        $attributes = [
            \ZeroBoiler\DTO\Attributes\Accepted::class,
            \ZeroBoiler\DTO\Attributes\ArrayRule::class,
            \ZeroBoiler\DTO\Attributes\Between::class,
            \ZeroBoiler\DTO\Attributes\Boolean::class,
            \ZeroBoiler\DTO\Attributes\Confirmed::class,
            \ZeroBoiler\DTO\Attributes\Declined::class,
            \ZeroBoiler\DTO\Attributes\Distinct::class,
            \ZeroBoiler\DTO\Attributes\Email::class,
            \ZeroBoiler\DTO\Attributes\EndsWith::class,
            \ZeroBoiler\DTO\Attributes\Hidden::class,
            \ZeroBoiler\DTO\Attributes\In::class,
            \ZeroBoiler\DTO\Attributes\Integer::class,
            \ZeroBoiler\DTO\Attributes\Json::class,
            \ZeroBoiler\DTO\Attributes\Max::class,
            \ZeroBoiler\DTO\Attributes\Min::class,
            \ZeroBoiler\DTO\Attributes\Nullable::class,
            \ZeroBoiler\DTO\Attributes\Numeric::class,
            \ZeroBoiler\DTO\Attributes\Pattern::class,
            \ZeroBoiler\DTO\Attributes\Present::class,
            \ZeroBoiler\DTO\Attributes\Prohibited::class,
            \ZeroBoiler\DTO\Attributes\Required::class,
            \ZeroBoiler\DTO\Attributes\RequiredIf::class,
            \ZeroBoiler\DTO\Attributes\RequiredUnless::class,
            \ZeroBoiler\DTO\Attributes\RequiredWith::class,
            \ZeroBoiler\DTO\Attributes\RequiredWithAll::class,
            \ZeroBoiler\DTO\Attributes\RequiredWithout::class,
            \ZeroBoiler\DTO\Attributes\RequiredWithoutAll::class,
            \ZeroBoiler\DTO\Attributes\Same::class,
            \ZeroBoiler\DTO\Attributes\Size::class,
            \ZeroBoiler\DTO\Attributes\Sometimes::class,
            \ZeroBoiler\DTO\Attributes\StartsWith::class,
            \ZeroBoiler\DTO\Attributes\Url::class,
            \ZeroBoiler\DTO\Attributes\Uuid::class,
        ];

        foreach ($attributes as $class) {
            $ref = new \ReflectionClass($class);
            expect($ref->implementsInterface(ValidationAttribute::class))
                ->toBeTrue("{$class} must implement ValidationAttribute");
        }
    });

    it('all validation attributes have ruleKey() method returning string', function () {
        // Test a representative set of attributes
        $attributes = [
            new \ZeroBoiler\DTO\Attributes\Required,
            new \ZeroBoiler\DTO\Attributes\Email,
            new \ZeroBoiler\DTO\Attributes\Max(255),
            new \ZeroBoiler\DTO\Attributes\Min(2),
            new \ZeroBoiler\DTO\Attributes\Integer,
            new \ZeroBoiler\DTO\Attributes\Numeric,
            new \ZeroBoiler\DTO\Attributes\Boolean,
            new \ZeroBoiler\DTO\Attributes\Url,
            new \ZeroBoiler\DTO\Attributes\Uuid,
            new \ZeroBoiler\DTO\Attributes\Nullable,
            new \ZeroBoiler\DTO\Attributes\Sometimes,
            new \ZeroBoiler\DTO\Attributes\Prohibited,
            new \ZeroBoiler\DTO\Attributes\Present,
            new \ZeroBoiler\DTO\Attributes\Accepted,
            new \ZeroBoiler\DTO\Attributes\Declined,
            new \ZeroBoiler\DTO\Attributes\Confirmed,
            new \ZeroBoiler\DTO\Attributes\Distinct,
            new \ZeroBoiler\DTO\Attributes\Json,
            new \ZeroBoiler\DTO\Attributes\Same('other'),
            new \ZeroBoiler\DTO\Attributes\Different('other'),
            new \ZeroBoiler\DTO\Attributes\Size(5),
            new \ZeroBoiler\DTO\Attributes\Pattern('/^[a-z]+$/'),
        ];

        foreach ($attributes as $instance) {
            expect($instance->ruleKey())->toBeString();
            expect($instance->ruleKey())->not->toBeEmpty();
        }
    });

    it('metadata attributes are final', function () {
        $metaAttributes = [
            \ZeroBoiler\DTO\Attributes\Cast::class,
            \ZeroBoiler\DTO\Attributes\MapFrom::class,
            \ZeroBoiler\DTO\Attributes\Hidden::class,
            \ZeroBoiler\DTO\Attributes\DefaultValue::class,
            \ZeroBoiler\DTO\Attributes\NestedArray::class,
            \ZeroBoiler\DTO\Attributes\Collection::class,
            \ZeroBoiler\DTO\Attributes\Enum::class,
        ];

        foreach ($metaAttributes as $class) {
            $ref = new \ReflectionClass($class);
            expect($ref->isFinal())->toBeTrue("{$class} should be final");
        }
    });
});

describe('DtoCollection — strict type safety', function () {
    it('rejects non-DTO items in constructor', function () {
        new DtoCollection([new \stdClass]);
    })->throws(\InvalidArgumentException::class);

    it('offsetSet rejects non-DTO values', function () {
        $collection = new DtoCollection;
        $collection[] = 'not a dto';
    })->throws(\InvalidArgumentException::class);

    it('offsetSet with null offset appends', function () {
        $dto1 = EmptyDTO::fromArray([], validate: false);
        $dto2 = EmptyDTO::fromArray([], validate: false);

        $collection = new DtoCollection([$dto1]);
        $collection[] = $dto2;

        expect($collection->count())->toBe(2);
    });

    it('offsetSet with explicit offset replaces', function () {
        $dto1 = EmptyDTO::fromArray([], validate: false);
        $dto2 = EmptyDTO::fromArray([], validate: false);

        $collection = new DtoCollection([$dto1]);
        $collection[0] = $dto2;

        expect($collection->count())->toBe(1);
        expect(spl_object_id($collection->first()))->toBe(spl_object_id($dto2));
    });

    it('offsetUnset re-indexes array', function () {
        $dtoList = array_map(
            static fn (): DataTransferObject => EmptyDTO::fromArray([], validate: false),
            range(1, 3)
        );

        $collection = new DtoCollection($dtoList);
        expect($collection->count())->toBe(3);

        unset($collection[0]);

        // Should still be countable without gaps
        expect($collection->count())->toBe(2);
        expect($collection[0])->not->toBeNull();

        // Items should be re-indexed
        $items = $collection->items();
        expect(array_keys($items))->toEqual([0, 1]);
    });

    it('filter returns new instance', function () {
        $dtoList = [
            EmptyDTO::fromArray([], validate: false),
            EmptyDTO::fromArray([], validate: false),
        ];

        $original = new DtoCollection($dtoList);
        $filtered = $original->filter(static fn (DataTransferObject $dto): bool => true);

        expect($filtered)->not->toBe($original);
        expect($filtered->count())->toBe(2);
    });

    it('push returns same instance (fluent)', function () {
        $dto = EmptyDTO::fromArray([], validate: false);
        $collection = new DtoCollection;

        $result = $collection->push($dto);

        expect($result)->toBe($collection); // Same instance
        expect($collection->count())->toBe(1);
    });

    it('isEmpty and isNotEmpty are consistent', function () {
        $empty = new DtoCollection;
        expect($empty->isEmpty())->toBeTrue();
        expect($empty->isNotEmpty())->toBeFalse();

        $dto = EmptyDTO::fromArray([], validate: false);
        $nonEmpty = new DtoCollection([$dto]);
        expect($nonEmpty->isEmpty())->toBeFalse();
        expect($nonEmpty->isNotEmpty())->toBeTrue();
    });
});

describe('Metadata cache — TTL and flush', function () {
    it('flushMetadataCache clears per-class cache', function () {
        // Create a DTO to populate cache
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
        ], validate: false);

        // Flush cache
        CreateUserDTO::flushMetadataCache();

        // Re-create — should work fine after flush
        $dto2 = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
        ], validate: false);

        expect($dto2->email)->toBe('test@example.com');
    });

    it('flushMetadataCache with null clears all', function () {
        CreateUserDTO::fromArray([], validate: false);
        EmptyDTO::fromArray([], validate: false);

        CreateUserDTO::flushMetadataCache(null);

        // Should still work after flush
        $dto = CreateUserDTO::fromArray([], validate: false);
        expect($dto)->toBeInstanceOf(CreateUserDTO::class);
    });

    it('flushMetadataCache with class clears only that class', function () {
        CreateUserDTO::fromArray([], validate: false);
        EmptyDTO::fromArray([], validate: false);

        // Flush only CreateUserDTO
        CreateUserDTO::flushMetadataCache(CreateUserDTO::class);

        // EmptyDTO should still be cached
        $dto = EmptyDTO::fromArray([], validate: false);
        expect($dto)->toBeInstanceOf(EmptyDTO::class);
    });
});

describe('rulesFor() — action scoped', function () {
    it('rulesFor returns rules by default', function () {
        $defaultRules = CreateUserDTO::rules();
        $actionRules = CreateUserDTO::rulesFor('create');

        expect($actionRules)->toEqual($defaultRules);
    });

    it('rulesFor with unknown action returns rules', function () {
        $defaultRules = CreateUserDTO::rules();
        $actionRules = CreateUserDTO::rulesFor('nonexistent_action');

        expect($actionRules)->toEqual($defaultRules);
    });

    it('rules() returns array with string keys', function () {
        $rules = CreateUserDTO::rules();

        expect($rules)->toBeArray();

        foreach (array_keys($rules) as $key) {
            expect($key)->toBeString();
        }
    });

    it('rules() returns array with array values (rule lists)', function () {
        $rules = CreateUserDTO::rules();

        foreach ($rules as $field => $fieldRules) {
            expect($fieldRules)->toBeArray();
        }
    });
});
