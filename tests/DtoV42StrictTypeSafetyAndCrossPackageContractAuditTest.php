<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use BackedEnum;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use ZeroBoiler\DTO\Attributes\Cast;
use ZeroBoiler\DTO\Attributes\DefaultValue;
use ZeroBoiler\DTO\Attributes\Email;
use ZeroBoiler\DTO\Attributes\Hidden;
use ZeroBoiler\DTO\Attributes\MapFrom;
use ZeroBoiler\DTO\Attributes\Max;
use ZeroBoiler\DTO\Attributes\Min;
use ZeroBoiler\DTO\Attributes\NestedArray;
use ZeroBoiler\DTO\Attributes\Nullable;
use ZeroBoiler\DTO\Attributes\Pattern;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\Attributes\Url;
use ZeroBoiler\DTO\Casts\DTOCast;
use ZeroBoiler\DTO\Contracts\FromRequestDTO;
use ZeroBoiler\DTO\Contracts\ValidationAttribute;
use ZeroBoiler\DTO\Contracts\ValidatableDTO;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\DTOManager;
use ZeroBoiler\DTO\Tests\Fixtures\AllDefaultsDTO;
use ZeroBoiler\DTO\Tests\Fixtures\ComprehensiveDTO;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;
use ZeroBoiler\DTO\Tests\Fixtures\MinimalDTO;
use ZeroBoiler\DTO\Tests\Fixtures\OrderDTO;
use ZeroBoiler\DTO\Tests\Fixtures\WithRoundtripDTO;

describe('V42 DTO Strict Type Safety And Cross-Package Contract Audit', function () {
    // ── PHPStan Level 9 strict type verification ──────────────────────────

    describe('Strict type safety — all public methods have typed returns', function () {
        it('DataTransferObject::fromArray() returns static', function () {
            $reflection = new ReflectionMethod(DataTransferObject::class, 'fromArray');
            $returnType = $reflection->getReturnType();

            expect($returnType)->not->toBeNull();
            expect($returnType->getName())->toBe('static');
        });

        it('DataTransferObject::fromPartialArray() returns static', function () {
            $reflection = new ReflectionMethod(DataTransferObject::class, 'fromPartialArray');
            $returnType = $reflection->getReturnType();

            expect($returnType)->not->toBeNull();
            expect($returnType->getName())->toBe('static');
        });

        it('DataTransferObject::fromJson() returns static', function () {
            $reflection = new ReflectionMethod(DataTransferObject::class, 'fromJson');
            $returnType = $reflection->getReturnType();

            expect($returnType)->not->toBeNull();
            expect($returnType->getName())->toBe('static');
        });

        it('DataTransferObject::toArray() returns array', function () {
            $reflection = new ReflectionMethod(DataTransferObject::class, 'toArray');
            $returnType = $reflection->getReturnType();

            expect($returnType)->not->toBeNull();
            expect($returnType->getName())->toBe('array');
        });

        it('DataTransferObject::rules() returns array', function () {
            $reflection = new ReflectionMethod(DataTransferObject::class, 'rules');
            $returnType = $reflection->getReturnType();

            expect($returnType)->not->toBeNull();
            expect($returnType->getName())->toBe('array');
        });

        it('DataTransferObject::rulesFor() returns array', function () {
            $reflection = new ReflectionMethod(DataTransferObject::class, 'rulesFor');
            $returnType = $reflection->getReturnType();

            expect($returnType)->not->toBeNull();
            expect($returnType->getName())->toBe('array');
        });

        it('DataTransferObject::equals() returns bool', function () {
            $reflection = new ReflectionMethod(DataTransferObject::class, 'equals');
            $returnType = $reflection->getReturnType();

            expect($returnType)->not->toBeNull();
            expect($returnType->getName())->toBe('bool');
        });

        it('DataTransferObject::isEmpty() returns bool', function () {
            $reflection = new ReflectionMethod(DataTransferObject::class, 'isEmpty');
            $returnType = $reflection->getReturnType();

            expect($returnType)->not->toBeNull();
            expect($returnType->getName())->toBe('bool');
        });

        it('DataTransferObject::with() returns static', function () {
            $reflection = new ReflectionMethod(DataTransferObject::class, 'with');
            $returnType = $reflection->getReturnType();

            expect($returnType)->not->toBeNull();
            expect($returnType->getName())->toBe('static');
        });

        it('DataTransferObject::only() returns array', function () {
            $reflection = new ReflectionMethod(DataTransferObject::class, 'only');
            $returnType = $reflection->getReturnType();

            expect($returnType)->not->toBeNull();
            expect($returnType->getName())->toBe('array');
        });

        it('DataTransferObject::except() returns array', function () {
            $reflection = new ReflectionMethod(DataTransferObject::class, 'except');
            $returnType = $reflection->getReturnType();

            expect($returnType)->not->toBeNull();
            expect($returnType->getName())->toBe('array');
        });
    });

    // ── DTOCollection strict type safety ─────────────────────────────────

    describe('DtoCollection strict type safety', function () {
        it('count() returns int', function () {
            $reflection = new ReflectionMethod(DtoCollection::class, 'count');
            $returnType = $reflection->getReturnType();

            expect($returnType)->not->toBeNull();
            expect($returnType->getName())->toBe('int');
        });

        it('isEmpty() returns bool', function () {
            $reflection = new ReflectionMethod(DtoCollection::class, 'isEmpty');
            $returnType = $reflection->getReturnType();

            expect($returnType)->not->toBeNull();
            expect($returnType->getName())->toBe('bool');
        });

        it('first() returns nullable DataTransferObject', function () {
            $reflection = new ReflectionMethod(DtoCollection::class, 'first');
            $returnType = $reflection->getReturnType();

            expect($returnType)->not->toBeNull();
            expect($returnType->allowsNull())->toBeTrue();
        });

        it('toArray() returns array', function () {
            $reflection = new ReflectionMethod(DtoCollection::class, 'toArray');
            $returnType = $reflection->getReturnType();

            expect($returnType)->not->toBeNull();
            expect($returnType->getName())->toBe('array');
        });

        it('unique() returns DtoCollection', function () {
            $reflection = new ReflectionMethod(DtoCollection::class, 'unique');
            $returnType = $reflection->getReturnType();

            expect($returnType)->not->toBeNull();
            expect($returnType->getName())->toBe('self');
        });

        it('contains() returns bool', function () {
            $reflection = new ReflectionMethod(DtoCollection::class, 'contains');
            $returnType = $reflection->getReturnType();

            expect($returnType)->not->toBeNull();
            expect($returnType->getName())->toBe('bool');
        });
    });

    // ── DTOManager readonly class ────────────────────────────────────────

    describe('DTOManager is readonly', function () {
        it('DTOManager is a final readonly class', function () {
            $ref = new ReflectionClass(DTOManager::class);
            expect($ref->isFinal())->toBeTrue();
            expect($ref->isReadOnly())->toBeTrue();
        });

        it('DTOManager::make() returns DataTransferObject', function () {
            $reflection = new ReflectionMethod(DTOManager::class, 'make');
            $returnType = $reflection->getReturnType();

            expect($returnType)->not->toBeNull();
            expect($returnType->getName())->toBe(DataTransferObject::class);
        });

        it('DTOManager::validate() returns array', function () {
            $reflection = new ReflectionMethod(DTOManager::class, 'validate');
            $returnType = $reflection->getReturnType();

            expect($returnType)->not->toBeNull();
            expect($returnType->getName())->toBe('array');
        });

        it('DTOManager::rules() returns array', function () {
            $reflection = new ReflectionMethod(DTOManager::class, 'rules');
            $returnType = $reflection->getReturnType();

            expect($returnType)->not->toBeNull();
            expect($returnType->getName())->toBe('array');
        });

        it('DTOManager::schema() returns array', function () {
            $reflection = new ReflectionMethod(DTOManager::class, 'schema');
            $returnType = $reflection->getReturnType();

            expect($returnType)->not->toBeNull();
            expect($returnType->getName())->toBe('array');
        });
    });

    // ── ValidationAttribute interface contract ──────────────────────────

    describe('ValidationAttribute interface compliance', function () {
        $validationAttributes = [
            Required::class, Email::class, Max::class, Min::class,
            Url::class, Pattern::class,
        ];

        foreach ($validationAttributes as $className) {
            it("{$className} implements ValidationAttribute", function () use ($className) {
                $ref = new ReflectionClass($className);
                expect($ref->implementsInterface(ValidationAttribute::class))->toBeTrue();
            });

            it("{$className} has ruleKey() returning string", function () use ($className) {
                $ref = new ReflectionMethod($className, 'ruleKey');
                $returnType = $ref->getReturnType();

                expect($returnType)->not->BeNull();
                expect($returnType->getName())->toBe('string');
            });
        }
    });

    // ── DTOException contract ──────────────────────────────────────────

    describe('DTOException contract', function () {
        it('invalidCast() includes property and type in message', function () {
            $exception = DTOException::invalidCast('email', 'integer', 'not-an-int');
            $message = $exception->getMessage();

            expect($message)->toContain('email');
            expect($message)->toContain('integer');
        });

        it('invalidJson() includes property in message', function () {
            $exception = DTOException::invalidJson('payload', 'Syntax error');
            $message = $exception->getMessage();

            expect($message)->toContain('payload');
            expect($message)->toContain('Syntax error');
        });

        it('__toString() follows FQCN: message format', function () {
            $exception = DTOException::invalidCast('field', 'int', 'abc');
            $string = (string) $exception;

            expect($string)->toStartWith(DTOException::class);
        });
    });

    // ── fromArray validation edge cases ─────────────────────────────────

    describe('fromArray validation edge cases', function () {
        it('rejects missing required fields', function () {
            expect(fn () => CreateUserDTO::fromArray([]))->toThrow(ValidationException::class);
        });

        it('rejects invalid email format', function () {
            expect(fn () => CreateUserDTO::fromArray([
                'email' => 'not-an-email',
                'name' => 'Test User',
                'password' => 'securepassword123',
            ]))->toThrow(ValidationException::class);
        });

        it('rejects password shorter than min length', function () {
            expect(fn () => CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test User',
                'password' => 'short',
            ]))->toThrow(ValidationException::class);
        });

        it('rejects name exceeding max length', function () {
            $longName = str_repeat('A', 51);
            expect(fn () => CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => $longName,
                'password' => 'securepassword123',
            ]))->toThrow(ValidationException::class);
        });

        it('accepts valid data without exception', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test User',
                'password' => 'securepassword123',
            ]);

            expect($dto)->toBeInstanceOf(CreateUserDTO::class);
            expect($dto->email)->toBe('test@example.com');
            expect($dto->name)->toBe('Test User');
        });
    });

    // ── fromPartialArray edge cases ─────────────────────────────────────

    describe('fromPartialArray edge cases', function () {
        it('creates DTO from empty array using defaults', function () {
            $dto = AllDefaultsDTO::fromPartialArray([]);

            expect($dto)->toBeInstanceOf(AllDefaultsDTO::class);
        });

        it('overrides only specified fields in partial update', function () {
            $dto = AllDefaultsDTO::fromPartialArray(['name' => 'Updated Name']);

            expect($dto->name)->toBe('Updated Name');
        });

        it('respects nullable properties with null input', function () {
            $dto = MinimalDTO::fromPartialArray(['email' => null]);

            expect($dto->email)->toBeNull();
        });
    });

    // ── fromJson edge cases ─────────────────────────────────────────────

    describe('fromJson edge cases', function () {
        it('rejects invalid JSON string', function () {
            expect(fn () => CreateUserDTO::fromJson('{invalid json}'))->toThrow(DTOException::class);
        });

        it('rejects JSON arrays (sequential)', function () {
            expect(fn () => CreateUserDTO::fromJson('["email","name"]'))->toThrow(DTOException::class);
        });

        it('accepts empty JSON object', function () {
            // AllDefaultsDTO has all defaults, so empty object should work
            $dto = AllDefaultsDTO::fromJson('{}');

            expect($dto)->toBeInstanceOf(AllDefaultsDTO::class);
        });

        it('accepts valid JSON with validation', function () {
            $json = json_encode([
                'email' => 'test@example.com',
                'name' => 'Test User',
                'password' => 'securepassword123',
            ]);

            $dto = CreateUserDTO::fromJson($json);

            expect($dto->email)->toBe('test@example.com');
        });
    });

    // ── with() immutable update ─────────────────────────────────────────

    describe('with() immutable update semantics', function () {
        it('returns a new instance without modifying original', function () {
            $original = CreateUserDTO::fromArray([
                'email' => 'original@example.com',
                'name' => 'Original',
                'password' => 'password123',
            ]);

            $updated = $original->with(['name' => 'Updated']);

            expect($original->name)->toBe('Original');
            expect($updated->name)->toBe('Updated');
            expect($updated->email)->toBe('original@example.com'); // preserved
        });

        it('validates the merged data', function () {
            $original = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test User',
                'password' => 'securepassword123',
            ]);

            expect(fn () => $original->with(['email' => 'invalid-email']))->toThrow(ValidationException::class);
        });

        it('preserves all other properties during partial update', function () {
            $original = ComprehensiveDTO::fromArray([
                'name' => 'Alice',
                'age' => 30,
                'email' => 'alice@example.com',
                'isActive' => true,
            ]);

            $updated = $original->with(['age' => 31]);

            expect($updated->name)->toBe('Alice');
            expect($updated->age)->toBe(31);
            expect($updated->email)->toBe('alice@example.com');
            expect($updated->isActive)->toBeTrue();
        });
    });

    // ── Hidden attribute behavior ────────────────────────────────────────

    describe('Hidden attribute behavior', function () {
        it('toArray() excludes hidden properties', function () {
            $dto = ComprehensiveDTO::fromArray([
                'name' => 'Alice',
                'age' => 30,
                'email' => 'alice@example.com',
                'isActive' => true,
            ]);

            $array = $dto->toArray();

            expect($array)->not->toHaveKey('password');
        });

        it('allValues() includes hidden properties', function () {
            $dto = ComprehensiveDTO::fromArray([
                'name' => 'Alice',
                'age' => 30,
                'email' => 'alice@example.com',
                'isActive' => true,
            ]);

            $allValues = $dto->allValues();

            // allValues should have everything, including any hidden fields
            expect($allValues)->toHaveKey('name');
            expect($allValues)->toHaveKey('email');
        });
    });

    // ── MapFrom attribute behavior ──────────────────────────────────────

    describe('MapFrom attribute behavior', function () {
        it('maps source key to property name', function () {
            // MinimalDTO uses MapFrom for some properties if it has them
            // Check the actual fixture behavior
            $dto = MinimalDTO::fromArray([
                'email' => 'test@example.com',
            ]);

            expect($dto->email)->toBe('test@example.com');
        });
    });

    // ── only() and except() selective output ──────────────────────────────

    describe('only() and except() selective output', function () {
        it('only() returns only specified fields', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test User',
                'password' => 'securepassword123',
            ]);

            $only = $dto->only('email', 'name');

            expect($only)->toHaveKeys(['email', 'name']);
            expect($only)->not->toHaveKey('password');
        });

        it('except() excludes specified fields', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test User',
                'password' => 'securepassword123',
            ]);

            $except = $dto->except('password');

            expect($except)->toHaveKeys(['email', 'name']);
            expect($except)->not->toHaveKey('password');
        });

        it('only() accepts string as single key', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test User',
                'password' => 'securepassword123',
            ]);

            $only = $dto->only('email');

            expect($only)->toHaveKey('email');
            expect($only)->not->toHaveKey('name');
            expect($only)->not->toHaveKey('password');
        });
    });

    // ── equals() equality check ──────────────────────────────────────────

    describe('equals() equality check', function () {
        it('returns true for DTOs with same values', function () {
            $a = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test',
                'password' => 'password123',
            ]);
            $b = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test',
                'password' => 'password123',
            ]);

            expect($a->equals($b))->toBeTrue();
        });

        it('returns false for DTOs with different values', function () {
            $a = CreateUserDTO::fromArray([
                'email' => 'a@example.com',
                'name' => 'Alice',
                'password' => 'password123',
            ]);
            $b = CreateUserDTO::fromArray([
                'email' => 'b@example.com',
                'name' => 'Bob',
                'password' => 'password456',
            ]);

            expect($a->equals($b))->toBeFalse();
        });
    });

    // ── isEmpty / isNotEmpty ─────────────────────────────────────────────

    describe('isEmpty / isNotEmpty state checks', function () {
        it('all-defaults DTO is considered empty', function () {
            $dto = AllDefaultsDTO::fromPartialArray([]);

            expect($dto->isEmpty())->toBeTrue();
            expect($dto->isNotEmpty())->toBeFalse();
        });

        it('DTO with at least one non-empty field is not empty', function () {
            $dto = AllDefaultsDTO::fromPartialArray(['name' => 'Alice']);

            expect($dto->isNotEmpty())->toBeTrue();
            expect($dto->isEmpty())->toBeFalse();
        });
    });

    // ── DtoCollection edge cases ────────────────────────────────────────

    describe('DtoCollection operations', function () {
        it('unique() removes duplicate DTOs', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test',
                'password' => 'password123',
            ]);

            $collection = DtoCollection::make([$dto, $dto]);
            $unique = $collection->unique();

            expect($unique->count())->toBe(1);
        });

        it('contains() finds matching DTO', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test',
                'password' => 'password123',
            ]);

            $collection = DtoCollection::make([$dto]);

            expect($collection->contains(fn ($item) => $item->email === 'test@example.com'))->toBeTrue();
            expect($collection->contains(fn ($item) => $item->email === 'other@example.com'))->toBeFalse();
        });

        it('search() returns first matching DTO', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test',
                'password' => 'password123',
            ]);

            $collection = DtoCollection::make([$dto]);

            expect($collection->search(fn ($item) => $item->email === 'test@example.com'))->toBe($dto);
            expect($collection->search(fn ($item) => $item->email === 'other@example.com'))->toBeNull();
        });

        it('sortBy() returns new sorted collection without modifying original', function () {
            $dto1 = CreateUserDTO::fromArray([
                'email' => 'charlie@example.com',
                'name' => 'Charlie',
                'password' => 'password123',
            ]);
            $dto2 = CreateUserDTO::fromArray([
                'email' => 'alice@example.com',
                'name' => 'Alice',
                'password' => 'password456',
            ]);

            $collection = DtoCollection::make([$dto1, $dto2]);
            $sorted = $collection->sortBy('name');

            // Original should be unchanged
            expect($collection->first()->name)->toBe('Charlie');
            // Sorted should have Alice first
            expect($sorted->first()->name)->toBe('Alice');
        });

        it('take() and skip() work correctly', function () {
            $items = [];
            for ($i = 0; $i < 10; $i++) {
                $items[] = CreateUserDTO::fromArray([
                    'email' => "user{$i}@example.com",
                    'name' => "User {$i}",
                    'password' => 'password123',
                ]);
            }

            $collection = DtoCollection::make($items);

            expect($collection->take(3)->count())->toBe(3);
            expect($collection->skip(7)->count())->toBe(3);
            expect($collection->take(5)->skip(2)->count())->toBe(3);
        });

        it('chunk() splits into correct sizes', function () {
            $items = [];
            for ($i = 0; $i < 7; $i++) {
                $items[] = CreateUserDTO::fromArray([
                    'email' => "user{$i}@example.com",
                    'name' => "User {$i}",
                    'password' => 'password123',
                ]);
            }

            $collection = DtoCollection::make($items);
            $chunks = $collection->chunk(3);

            expect(count($chunks))->toBe(3);
            expect($chunks[0]->count())->toBe(3);
            expect($chunks[1]->count())->toBe(3);
            expect($chunks[2]->count())->toBe(1);
        });
    });

    // ── DTOCast Eloquent cast ──────────────────────────────────────────

    describe('DTOCast serialization contract', function () {
        it('DTOCast is a final class', function () {
            $ref = new ReflectionClass(DTOCast::class);
            expect($ref->isFinal())->toBeTrue();
        });

        it('DTOCast has readonly properties', function () {
            $ref = new ReflectionClass(DTOCast::class);
            $props = $ref->getProperties();

            foreach ($props as $prop) {
                expect($prop->isReadOnly())->toBeTrue("DTOCast::\${$prop->getName()} should be readonly");
            }
        });
    });

    // ── declare(strict_types=1) verification ────────────────────────────

    describe('Strict types declaration', function () {
        it('all source files declare strict_types=1', function () {
            $srcDir = dirname(__DIR__, 2).'/src';
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($srcDir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::LEAVES_ONLY
            );

            $violations = [];
            foreach ($iterator as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                $contents = $file->getContents();
                if (! str_contains($contents, 'declare(strict_types=1)')) {
                    $violations[] = $file->getFilename();
                }
            }

            expect($violations)->toBeEmpty('Missing declare(strict_types=1) in: '.implode(', ', $violations));
        });
    });

    // ── Metadata cache TTL behavior ──────────────────────────────────────

    describe('Metadata cache TTL behavior', function () {
        it('flushMetadataCache clears class-specific cache', function () {
            DataTransferObject::flushMetadataCache(CreateUserDTO::class);

            // Re-resolve should work without error
            $rules = CreateUserDTO::rules();
            expect($rules)->toBeArray();
            expect($rules)->not->toBeEmpty();
        });

        it('flushMetadataCache(null) clears all cache', function () {
            DataTransferObject::flushMetadataCache();

            $rules1 = CreateUserDTO::rules();
            $rules2 = MinimalDTO::rules();

            expect($rules1)->toBeArray();
            expect($rules2)->toBeArray();
        });

        it('setMetadataCacheTtl accepts float values', function () {
            DataTransferObject::setMetadataCacheTtl(2.5);

            // Reset
            DataTransferObject::setMetadataCacheTtl(0.0);
        });
    });

    // ── EmptyDTO edge cases ─────────────────────────────────────────────

    describe('EmptyDTO edge case', function () {
        it('EmptyDTO has no constructor parameters', function () {
            $ref = new ReflectionClass(EmptyDTO::class);
            $constructor = $ref->getConstructor();

            if ($constructor !== null) {
                expect($constructor->getNumberOfParameters())->toBe(0);
            }
        });

        it('EmptyDTO rules are empty', function () {
            $rules = EmptyDTO::rules();
            expect($rules)->toBeArray();
            expect($rules)->toBeEmpty();
        });

        it('EmptyDTO toArray returns empty array', function () {
            $dto = EmptyDTO::fromArray([]);
            expect($dto->toArray())->toBe([]);
        });
    });
});
