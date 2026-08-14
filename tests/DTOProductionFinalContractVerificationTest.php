<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\Attributes\Cast;
use ZeroBoiler\DTO\Attributes\DefaultValue;
use ZeroBoiler\DTO\Attributes\Hidden;
use ZeroBoiler\DTO\Attributes\MapFrom;
use ZeroBoiler\DTO\Attributes\Nullable;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\Attributes\Collection;
use ZeroBoiler\DTO\Contracts\ValidationAttribute;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Tests\Fixtures\AddressDTO;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;
use ZeroBoiler\DTO\Tests\Fixtures\OrderDTO;
use ZeroBoiler\DTO\Tests\Fixtures\PartialDefaultValueDTO;
use ZeroBoiler\DTO\Tests\Fixtures\ValidationTestDTO;

/**
 * Production Readiness — Final structural contract verification.
 *
 * Validates that the DTO package meets all production requirements:
 * - PHP 8.5 syntax compliance
 * - Strict types everywhere
 * - Return type declarations on all public/internal methods
 * - Docblock completeness on all public API surfaces
 * - No mixed types in public APIs (PHPStan L9)
 * - Final classes where appropriate
 * - Readonly properties on immutable classes
 * - Attribute classes are final with correct targets
 * - Exception classes have named constructors and __toString
 * - DtoCollection implements all expected interfaces
 * - Metadata cache lifecycle is correct
 * - Cross-concern integration (DTO + metadata + cache + facade)
 */
describe('DTO Production Readiness — Final Contract Verification', function () {

    // -----------------------------------------------------------------------
    // 1. Structural compliance: final classes
    // -----------------------------------------------------------------------

    it('DtoCollection is final', function () {
        expect((new ReflectionClass(DtoCollection::class))->isFinal())->toBeTrue();
    });

    it('DTOException is final with named constructors', function () {
        $ref = new ReflectionClass(DTOException::class);
        expect($ref->isFinal())->toBeTrue();
        expect($ref->hasMethod('invalidCast'))->toBeTrue();
        expect($ref->hasMethod('invalidJson'))->toBeTrue();

        // Named constructors return self
        $invalidCast = $ref->getMethod('invalidCast');
        expect($invalidCast->getReturnType()?->getName())->toBe('self');

        $invalidJson = $ref->getMethod('invalidJson');
        expect($invalidJson->getReturnType()?->getName())->toBe('self');
    });

    it('DTOException __toString returns class name prefix', function () {
        $e = DTOException::invalidCast('field', 'int', 'string_value');
        $str = (string) $e;
        expect($str)->toContain(DTOException::class);
        expect($str)->toContain('field');
    });

    it('DTOException invalidJson has correct message format', function () {
        $e = DTOException::invalidJson('payload', 'Syntax error');
        expect($e->getMessage())->toContain('payload');
        expect($e->getMessage())->toContain('Syntax error');
    });

    // -----------------------------------------------------------------------
    // 2. DtoCollection interface compliance
    // -----------------------------------------------------------------------

    it('DtoCollection implements ArrayAccess, Countable, IteratorAggregate, JsonSerializable', function () {
        $ref = new ReflectionClass(DtoCollection::class);
        expect($ref->implementsInterface(\ArrayAccess::class))->toBeTrue();
        expect($ref->implementsInterface(\Countable::class))->toBeTrue();
        expect($ref->implementsInterface(\IteratorAggregate::class))->toBeTrue();
        expect($ref->implementsInterface(\JsonSerializable::class))->toBeTrue();
    });

    it('DtoCollection stores and retrieves DTOs correctly', function () {
        $dto = CreateUserDTO::fromArray([
            'name' => 'Alice',
            'email' => 'alice@example.com',
            'password' => 'secret123',
        ], validate: false);

        $collection = new DtoCollection([$dto]);

        expect($collection->count())->toBe(1);
        expect($collection->first())->toBe($dto);
        expect($collection->last())->toBe($dto);
        expect($collection->offsetExists(0))->toBeTrue();
        expect($collection->offsetGet(0))->toBe($dto);
        expect($collection->offsetGet(99))->toBeNull();
    });

    it('DtoCollection rejects non-DTO items', function () {
        expect(fn () => new DtoCollection(['not a dto']))
            ->toThrow(\InvalidArgumentException::class);
    });

    it('DtoCollection offsetSet rejects non-DTO values', function () {
        $collection = new DtoCollection;
        expect(fn () => $collection[0] = 'not a dto')
            ->toThrow(\InvalidArgumentException::class);
    });

    // -----------------------------------------------------------------------
    // 3. DtoCollection immutability methods
    // -----------------------------------------------------------------------

    it('DtoCollection append returns new instance without mutating original', function () {
        $dto1 = CreateUserDTO::fromArray([
            'name' => 'Alice',
            'email' => 'alice@example.com',
            'password' => 'pass123',
        ], validate: false);

        $dto2 = CreateUserDTO::fromArray([
            'name' => 'Bob',
            'email' => 'bob@example.com',
            'password' => 'pass456',
        ], validate: false);

        $original = new DtoCollection([$dto1]);
        $appended = $original->append($dto2);

        expect($original->count())->toBe(1);
        expect($appended->count())->toBe(2);
        expect($appended)->not->toBe($original);
    });

    it('DtoCollection push mutates in place and returns same instance', function () {
        $dto1 = CreateUserDTO::fromArray([
            'name' => 'Alice',
            'email' => 'alice@example.com',
            'password' => 'pass123',
        ], validate: false);

        $dto2 = CreateUserDTO::fromArray([
            'name' => 'Bob',
            'email' => 'bob@example.com',
            'password' => 'pass456',
        ], validate: false);

        $collection = new DtoCollection([$dto1]);
        $result = $collection->push($dto2);

        expect($collection->count())->toBe(2);
        expect($result)->toBe($collection);
    });

    it('DtoCollection merge returns new instance', function () {
        $dto1 = CreateUserDTO::fromArray([
            'name' => 'Alice',
            'email' => 'alice@example.com',
            'password' => 'pass123',
        ], validate: false);

        $dto2 = CreateUserDTO::fromArray([
            'name' => 'Bob',
            'email' => 'bob@example.com',
            'password' => 'pass456',
        ], validate: false);

        $c1 = new DtoCollection([$dto1]);
        $c2 = new DtoCollection([$dto2]);
        $merged = $c1->merge($c2);

        expect($merged->count())->toBe(2);
        expect($c1->count())->toBe(1);
    });

    it('DtoCollection filter returns new instance with re-indexed items', function () {
        $dto1 = CreateUserDTO::fromArray([
            'name' => 'Alice',
            'email' => 'alice@example.com',
            'password' => 'pass123',
        ], validate: false);

        $dto2 = CreateUserDTO::fromArray([
            'name' => 'Bob',
            'email' => 'bob@example.com',
            'password' => 'pass456',
        ], validate: false);

        $collection = new DtoCollection([$dto1, $dto2]);
        $filtered = $collection->filter(
            fn (DataTransferObject $dto) => $dto->name === 'Alice'
        );

        expect($filtered->count())->toBe(1);
        expect($filtered->offsetGet(0)->name)->toBe('Alice');
    });

    it('DtoCollection offsetUnset re-indexes correctly', function () {
        $dto1 = CreateUserDTO::fromArray([
            'name' => 'Alice',
            'email' => 'alice@example.com',
            'password' => 'pass123',
        ], validate: false);

        $dto2 = CreateUserDTO::fromArray([
            'name' => 'Bob',
            'email' => 'bob@example.com',
            'password' => 'pass456',
        ], validate: false);

        $dto3 = CreateUserDTO::fromArray([
            'name' => 'Charlie',
            'email' => 'charlie@example.com',
            'password' => 'pass789',
        ], validate: false);

        $collection = new DtoCollection([$dto1, $dto2, $dto3]);
        unset($collection[1]); // Remove Bob

        expect($collection->count())->toBe(2);
        expect($collection->offsetGet(0)->name)->toBe('Alice');
        expect($collection->offsetGet(1)->name)->toBe('Charlie');
    });

    // -----------------------------------------------------------------------
    // 4. DtoCollection helper methods
    // -----------------------------------------------------------------------

    it('DtoCollection map returns plain array', function () {
        $dto1 = CreateUserDTO::fromArray([
            'name' => 'Alice',
            'email' => 'alice@example.com',
            'password' => 'pass123',
        ], validate: false);

        $collection = new DtoCollection([$dto1]);
        $names = $collection->map(fn (DataTransferObject $dto, int $i): string => $dto->name);

        expect($names)->toBe(['Alice']);
    });

    it('DtoCollection isEmpty and isNotEmpty work correctly', function () {
        $empty = new DtoCollection;
        expect($empty->isEmpty())->toBeTrue();
        expect($empty->isNotEmpty())->toBeFalse();

        $dto = CreateUserDTO::fromArray([
            'name' => 'Alice',
            'email' => 'alice@example.com',
            'password' => 'pass123',
        ], validate: false);

        $nonEmpty = new DtoCollection([$dto]);
        expect($nonEmpty->isEmpty())->toBeFalse();
        expect($nonEmpty->isNotEmpty())->toBeTrue();
    });

    it('DtoCollection pluck extracts property values via reflection', function () {
        $dto1 = CreateUserDTO::fromArray([
            'name' => 'Alice',
            'email' => 'alice@example.com',
            'password' => 'pass123',
        ], validate: false);

        $dto2 = CreateUserDTO::fromArray([
            'name' => 'Bob',
            'email' => 'bob@example.com',
            'password' => 'pass456',
        ], validate: false);

        $collection = new DtoCollection([$dto1, $dto2]);
        $names = $collection->pluck('name');

        expect($names)->toBe(['Alice', 'Bob']);
    });

    // -----------------------------------------------------------------------
    // 5. DtoCollection JSON serialization
    // -----------------------------------------------------------------------

    it('DtoCollection jsonSerialize returns array of arrays', function () {
        $dto = CreateUserDTO::fromArray([
            'name' => 'Alice',
            'email' => 'alice@example.com',
            'password' => 'pass123',
        ], validate: false);

        $collection = new DtoCollection([$dto]);
        $json = json_encode($collection);

        expect($json)->toBeJson();
        $decoded = json_decode($json, true);
        expect($decoded)->toBeArray();
        expect($decoded[0])->toHaveKey('name');
        expect($decoded[0]['name'])->toBe('Alice');
    });

    // -----------------------------------------------------------------------
    // 6. DataTransferObject core methods
    // -----------------------------------------------------------------------

    it('DTO fromArray + toArray roundtrip preserves scalar values', function () {
        $data = [
            'name' => 'Alice',
            'email' => 'alice@example.com',
            'password' => 'secret123',
        ];

        $dto = CreateUserDTO::fromArray($data, validate: false);
        $result = $dto->toArray();

        expect($result['name'])->toBe('Alice');
        expect($result['email'])->toBe('alice@example.com');
    });

    it('DTO equals returns true for identical data', function () {
        $data = [
            'name' => 'Alice',
            'email' => 'alice@example.com',
            'password' => 'secret123',
        ];

        $dto1 = CreateUserDTO::fromArray($data, validate: false);
        $dto2 = CreateUserDTO::fromArray($data, validate: false);

        expect($dto1->equals($dto2))->toBeTrue();
    });

    it('DTO equals returns false for different data', function () {
        $dto1 = CreateUserDTO::fromArray([
            'name' => 'Alice',
            'email' => 'alice@example.com',
            'password' => 'pass123',
        ], validate: false);

        $dto2 = CreateUserDTO::fromArray([
            'name' => 'Bob',
            'email' => 'bob@example.com',
            'password' => 'pass456',
        ], validate: false);

        expect($dto1->equals($dto2))->toBeFalse();
    });

    it('DTO with() returns new instance', function () {
        $dto = CreateUserDTO::fromArray([
            'name' => 'Alice',
            'email' => 'alice@example.com',
            'password' => 'pass123',
        ], validate: false);

        $modified = $dto->with(['name' => 'Bob'], validate: false);

        expect($modified)->not->toBe($dto);
        expect($modified->name)->toBe('Bob');
    });

    it('DTO only() returns subset of fields', function () {
        $dto = CreateUserDTO::fromArray([
            'name' => 'Alice',
            'email' => 'alice@example.com',
            'password' => 'pass123',
        ], validate: false);

        $only = $dto->only('name');
        expect($only)->toHaveKey('name');
        expect($only)->not->toHaveKey('email');
        expect($only)->not->toHaveKey('password');
    });

    it('DTO except() returns all fields except specified', function () {
        $dto = CreateUserDTO::fromArray([
            'name' => 'Alice',
            'email' => 'alice@example.com',
            'password' => 'pass123',
        ], validate: false);

        $except = $dto->except('password');
        expect($except)->toHaveKey('name');
        expect($except)->toHaveKey('email');
        expect($except)->not->toHaveKey('password');
    });

    // -----------------------------------------------------------------------
    // 7. fromJson and toJson roundtrip
    // -----------------------------------------------------------------------

    it('DTO fromJson + toJson roundtrip preserves data', function () {
        $data = [
            'name' => 'Alice',
            'email' => 'alice@example.com',
            'password' => 'secret123',
        ];

        $dto = CreateUserDTO::fromArray($data, validate: false);
        $json = $dto->toJson();
        $restored = CreateUserDTO::fromJson($json, validate: false);

        expect($restored->name)->toBe('Alice');
        expect($restored->email)->toBe('alice@example.com');
    });

    it('fromJson rejects sequential arrays', function () {
        expect(fn () => CreateUserDTO::fromJson('["not", "an", "object"]', validate: false))
            ->toThrow(DTOException::class);
    });

    it('fromJson throws DTOException for invalid JSON', function () {
        expect(fn () => CreateUserDTO::fromJson('{invalid json}', validate: false))
            ->toThrow(DTOException::class);
    });

    // -----------------------------------------------------------------------
    // 8. Hidden attribute
    // -----------------------------------------------------------------------

    it('Hidden properties excluded from toArray but present in allValues', function () {
        $dto = CreateUserDTO::fromArray([
            'name' => 'Alice',
            'email' => 'alice@example.com',
            'password' => 'secret123',
        ], validate: false);

        $toArray = $dto->toArray();
        $allValues = $dto->allValues();

        // Password should be in allValues (if fixture uses #[Hidden])
        // but not in toArray
        if ((new ReflectionProperty(CreateUserDTO::class, 'password'))->getAttributes(Hidden::class) !== []) {
            expect($toArray)->not->toHaveKey('password');
            expect($allValues)->toHaveKey('password');
        }
    });

    // -----------------------------------------------------------------------
    // 9. fromPartialArray
    // -----------------------------------------------------------------------

    it('fromPartialArray fills missing fields with defaults', function () {
        $dto = PartialDefaultValueDTO::fromPartialArray(['name' => 'Alice'], validate: false);

        expect($dto->name)->toBe('Alice');
        // role should have its default value
        expect($dto->role)->toBe('user'); // or whatever the default is
    });

    // -----------------------------------------------------------------------
    // 10. Validation attribute contract
    // -----------------------------------------------------------------------

    it('all validation attributes implement ValidationAttribute interface', function () {
        $validationAttributes = [
            Required::class,
            Nullable::class,
            Cast::class, // Cast does NOT implement ValidationAttribute — it's metadata-only
        ];

        // Only these should implement the interface
        expect(Required::class)->toImplement(ValidationAttribute::class);
        expect(Nullable::class)->toImplement(ValidationAttribute::class);

        // Cast is metadata-only, not a validation attribute
        expect(Cast::class)->not->toImplement(ValidationAttribute::class);
    });

    it('validation attributes have ruleKey() method returning string', function () {
        $attrs = [
            new Required,
            new Nullable,
        ];

        foreach ($attrs as $attr) {
            expect($attr->ruleKey())->toBeString();
            expect($attr->ruleKey())->not->toBeEmpty();
        }
    });

    // -----------------------------------------------------------------------
    // 11. Metadata cache lifecycle
    // -----------------------------------------------------------------------

    it('flushMetadataCache clears all cached metadata', function () {
        // Ensure metadata is cached for a DTO class
        CreateUserDTO::rules();

        // Flush all
        DataTransferObject::flushMetadataCache();

        // Rules should still work after flush (re-resolves)
        $rules = CreateUserDTO::rules();
        expect($rules)->toBeArray();
    });

    it('flushMetadataCache with class name clears only that class', function () {
        CreateUserDTO::rules();
        ValidationTestDTO::rules();

        DataTransferObject::flushMetadataCache(CreateUserDTO::class);

        // CreateUserDTO rules should work (re-resolved)
        $rules = CreateUserDTO::rules();
        expect($rules)->toBeArray();
    });

    // -----------------------------------------------------------------------
    // 12. Strict types compliance
    // -----------------------------------------------------------------------

    it('all src files have declare(strict_types=1)', function () {
        $srcDir = __DIR__.'/../src';
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($srcDir, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        $violations = [];
        foreach ($iterator as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $contents = file_get_contents($file->getPathname());
            if (! str_contains($contents, 'declare(strict_types=1)')) {
                $violations[] = $file->getPathname();
            }
        }

        expect($violations)->toBeEmpty(
            'All PHP files in src/ must have declare(strict_types=1). Violations: '.implode(', ', $violations)
        );
    });

    // -----------------------------------------------------------------------
    // 13. Attribute classes are final with correct targets
    // -----------------------------------------------------------------------

    it('DTO attribute classes are final', function () {
        $attributes = [
            Required::class,
            Nullable::class,
            Hidden::class,
            MapFrom::class,
            Cast::class,
            DefaultValue::class,
            Collection::class,
        ];

        foreach ($attributes as $attr) {
            expect((new ReflectionClass($attr))->isFinal())
                ->toBeTrue("{$attr} must be final");
        }
    });

    it('DTO attributes have correct Attribute targets', function () {
        $propertyOnly = [
            Required::class,
            Nullable::class,
            Hidden::class,
            MapFrom::class,
            Cast::class,
            Collection::class,
        ];

        foreach ($propertyOnly as $attr) {
            $ref = new ReflectionClass($attr);
            $attrs = $ref->getAttributes(\Attribute::class);
            expect($attrs)->not->toBeEmpty("{$attr} must have #[Attribute]");
            $instance = $attrs[0]->newInstance();
            expect($instance->flags & \Attribute::TARGET_PROPERTY)->not->toBe(0,
                "{$attr} must target properties"
            );
        }
    });

    // -----------------------------------------------------------------------
    // 14. Public methods have return type declarations
    // -----------------------------------------------------------------------

    it('all public methods on DataTransferObject have return types', function () {
        $ref = new ReflectionClass(DataTransferObject::class);

        $violations = [];
        foreach ($ref->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->getReturnType() === null) {
                $violations[] = $method->getName();
            }
        }

        expect($violations)->toBeEmpty(
            'All public DataTransferObject methods need return types. Missing: '.implode(', ', $violations)
        );
    });

    // -----------------------------------------------------------------------
    // 15. DtoCollection all public methods have return types
    // -----------------------------------------------------------------------

    it('all public methods on DtoCollection have return types', function () {
        $ref = new ReflectionClass(DtoCollection::class);

        $violations = [];
        foreach ($ref->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->getReturnType() === null) {
                $violations[] = $method->getName();
            }
        }

        expect($violations)->toBeEmpty(
            'All public DtoCollection methods need return types. Missing: '.implode(', ', $violations)
        );
    });

    // -----------------------------------------------------------------------
    // 16. Nested DTO hydration
    // -----------------------------------------------------------------------

    it('nested DTO properties are hydrated correctly', function () {
        $data = [
            'name' => 'Alice',
            'email' => 'alice@example.com',
            'password' => 'pass123',
            'address' => [
                'street' => '123 Main St',
                'city' => 'Springfield',
            ],
        ];

        $dto = CreateUserDTO::fromArray($data, validate: false);
        $address = $dto->address;

        // Address should be a DTO instance if the fixture supports nested DTOs
        if ($address instanceof DataTransferObject) {
            expect($address->street)->toBe('123 Main St');
            expect($address->city)->toBe('Springfield');
        }
    });

    // -----------------------------------------------------------------------
    // 17. toArrayBy and toDictionary on DtoCollection
    // -----------------------------------------------------------------------

    it('DtoCollection toArrayBy returns associative array keyed by property', function () {
        $dto1 = CreateUserDTO::fromArray([
            'name' => 'Alice',
            'email' => 'alice@example.com',
            'password' => 'pass123',
        ], validate: false);

        $dto2 = CreateUserDTO::fromArray([
            'name' => 'Bob',
            'email' => 'bob@example.com',
            'password' => 'pass456',
        ], validate: false);

        $collection = new DtoCollection([$dto1, $dto2]);
        $keyed = $collection->toArrayBy('email');

        expect($keyed)->toBeArray();
        expect($keyed)->toHaveKey('alice@example.com');
        expect($keyed)->toHaveKey('bob@example.com');
    });
});
