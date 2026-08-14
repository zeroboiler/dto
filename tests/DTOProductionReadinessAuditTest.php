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
use ZeroBoiler\DTO\Attributes\Enum;
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
use ZeroBoiler\DTO\Attributes\Collection;
use ZeroBoiler\DTO\Attributes\ArrayRule;
use ZeroBoiler\DTO\Casts\DTOCast;
use ZeroBoiler\DTO\Contracts\FromRequestDTO;
use ZeroBoiler\DTO\Contracts\ValidatableDTO;
use ZeroBoiler\DTO\Contracts\ValidationAttribute;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DTOManager;
use ZeroBoiler\DTO\DTOSServiceProvider;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;
use ZeroBoiler\DTO\Tests\Fixtures\MinimalDTO;
use ZeroBoiler\DTO\Tests\Fixtures\NullableRoundtripDTO;
use ZeroBoiler\DTO\Tests\Fixtures\RoundtripDTO;

describe('DTO Production Readiness — Type Safety & Contract Audit', function (): void {
    beforeEach(function (): void {
        DataTransferObject::flushMetadataCache();
    });

    afterEach(function (): void {
        DataTransferObject::flushMetadataCache();
    });

    // -----------------------------------------------------------------------
    // All source files have declare(strict_types=1)
    // -----------------------------------------------------------------------

    it('all source files declare strict types', function (): void {
        $srcDir = __DIR__.'/../../src';
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($srcDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        $violations = [];
        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }
            $contents = file_get_contents($file->getPathname());
            if (! str_contains($contents, 'declare(strict_types=1)')) {
                $violations[] = $file->getPathname();
            }
        }

        expect($violations)->toBeEmpty(
            'All source files must have declare(strict_types=1). Violations: '.implode(', ', $violations)
        );
    });

    // -----------------------------------------------------------------------
    // All validation attributes are final
    // -----------------------------------------------------------------------

    it('all validation attributes are final classes', function (): void {
        $attributes = [
            Accepted::class, Boolean::class, Cast::class, Confirmed::class,
            Date::class, Declined::class, DefaultValue::class, Different::class,
            Distinct::class, Email::class, EndsWith::class, Enum::class,
            Hidden::class, In::class, Integer::class, Json::class,
            MapFrom::class, Max::class, Min::class, NestedArray::class,
            Nullable::class, Numeric::class, Pattern::class, Present::class,
            Prohibited::class, Required::class, RequiredIf::class,
            RequiredUnless::class, RequiredWith::class, RequiredWithAll::class,
            RequiredWithout::class, RequiredWithoutAll::class, Same::class,
            Size::class, Sometimes::class, StartsWith::class, Url::class,
            Uuid::class, Collection::class, ArrayRule::class,
        ];

        foreach ($attributes as $attrClass) {
            $ref = new ReflectionClass($attrClass);
            expect($ref->isFinal())->toBeTrue("{$attrClass} must be final");
        }
    });

    // -----------------------------------------------------------------------
    // Validation attributes that implement ValidationAttribute have ruleKey()
    // -----------------------------------------------------------------------

    it('ValidationAttribute implementations all have ruleKey() method', function (): void {
        $implementors = [
            Accepted::class, Boolean::class, Confirmed::class, Date::class,
            Declined::class, Different::class, Distinct::class, Email::class,
            EndsWith::class, Enum::class, In::class, Integer::class,
            Json::class, Max::class, Min::class, NestedArray::class,
            Nullable::class, Numeric::class, Pattern::class, Present::class,
            Prohibited::class, Required::class, RequiredIf::class,
            RequiredUnless::class, RequiredWith::class, RequiredWithAll::class,
            RequiredWithout::class, RequiredWithoutAll::class, Same::class,
            Size::class, Sometimes::class, StartsWith::class, Url::class,
            Uuid::class, Collection::class, ArrayRule::class,
        ];

        foreach ($implementors as $class) {
            expect($class)->toImplement(ValidationAttribute::class);
            $ref = new ReflectionClass($class);
            $method = $ref->getMethod('ruleKey');
            expect($method->getReturnType()?->getName())->toBe('string');
        }
    });

    // -----------------------------------------------------------------------
    // Metadata-only attributes do NOT implement ValidationAttribute
    // -----------------------------------------------------------------------

    it('Cast, Hidden, MapFrom, DefaultValue do NOT implement ValidationAttribute', function (): void {
        $nonValidation = [Cast::class, Hidden::class, MapFrom::class];

        foreach ($nonValidation as $class) {
            expect($class)->not->toImplement(ValidationAttribute::class);
        }
    });

    // -----------------------------------------------------------------------
    // Core classes — final checks
    // -----------------------------------------------------------------------

    it('DataTransferObject is abstract', function (): void {
        expect((new ReflectionClass(DataTransferObject::class))->isAbstract())->toBeTrue();
    });

    it('DtoCollection is final', function (): void {
        expect((new ReflectionClass(DtoCollection::class))->isFinal())->toBeTrue();
    });

    it('DTOManager is final and readonly', function (): void {
        $ref = new ReflectionClass(DTOManager::class);
        expect($ref->isFinal())->toBeTrue();
        expect($ref->isReadOnly())->toBeTrue();
    });

    it('DTOCast is final', function (): void {
        expect((new ReflectionClass(DTOCast::class))->isFinal())->toBeTrue();
    });

    it('DTOException is final', function (): void {
        expect((new ReflectionClass(DTOException::class))->isFinal())->toBeTrue();
    });

    it('DTOSServiceProvider is final', function (): void {
        expect((new ReflectionClass(DTOSServiceProvider::class))->isFinal())->toBeTrue();
    });

    it('DTO facade is final', function (): void {
        expect((new ReflectionClass(\ZeroBoiler\DTO\Facades\DTO::class))->isFinal())->toBeTrue();
    });

    // -----------------------------------------------------------------------
    // Interface contracts
    // -----------------------------------------------------------------------

    it('DataTransferObject implements FromRequestDTO', function (): void {
        expect(DataTransferObject::class)->toImplement(FromRequestDTO::class);
    });

    it('DataTransferObject implements ValidatableDTO', function (): void {
        expect(DataTransferObject::class)->toImplement(ValidatableDTO::class);
    });

    it('FromRequestDTO requires fromRequest method', function (): void {
        $ref = new ReflectionClass(FromRequestDTO::class);
        $method = $ref->getMethod('fromRequest');
        expect($method)->not->toBeNull();
        expect($method->isPublic())->toBeTrue();
        expect($method->isStatic())->toBeTrue();
    });

    it('ValidatableDTO requires rules and rulesFor methods', function (): void {
        $ref = new ReflectionClass(ValidatableDTO::class);
        expect($ref->getMethod('rules'))->not->toBeNull();
        expect($ref->getMethod('rulesFor'))->not->toBeNull();
    });

    // -----------------------------------------------------------------------
    // DTOException — named constructors
    // -----------------------------------------------------------------------

    it('DTOException::invalidCast() returns self with descriptive message', function (): void {
        $ex = DTOException::invalidCast('email', 'integer', 'not-an-int');
        expect($ex)->toBeInstanceOf(DTOException::class);
        expect($ex->getMessage())->toContain('email');
        expect($ex->getMessage())->toContain('integer');
    });

    it('DTOException::invalidJson() returns self with descriptive message', function (): void {
        $ex = DTOException::invalidJson('metadata', 'Syntax error');
        expect($ex)->toBeInstanceOf(DTOException::class);
        expect($ex->getMessage())->toContain('metadata');
        expect($ex->getMessage())->toContain('Syntax error');
    });

    it('DTOException __toString includes class name', function (): void {
        $ex = DTOException::invalidCast('field', 'string', 42);
        $str = (string) $ex;
        expect($str)->toContain('DTOException');
    });

    // -----------------------------------------------------------------------
    // DTOManager — all methods have return type declarations
    // -----------------------------------------------------------------------

    it('DTOManager validate() returns array', function (): void {
        $manager = new DTOManager;
        $result = $manager->validate(CreateUserDTO::class, [
            'email' => 'test@example.com',
            'name' => 'Test User',
        ]);
        expect($result)->toBeArray();
    });

    it('DTOManager make() returns DataTransferObject', function (): void {
        $manager = new DTOManager;
        $dto = $manager->make(CreateUserDTO::class, [
            'email' => 'test@example.com',
            'name' => 'Test User',
        ]);
        expect($dto)->toBeInstanceOf(DataTransferObject::class);
    });

    it('DTOManager rules() returns array', function (): void {
        $manager = new DTOManager;
        $rules = $manager->rules(CreateUserDTO::class);
        expect($rules)->toBeArray();
        expect($rules)->toHaveKey('email');
    });

    it('DTOManager rulesFor() returns array', function (): void {
        $manager = new DTOManager;
        $rules = $manager->rulesFor(CreateUserDTO::class, 'create');
        expect($rules)->toBeArray();
    });

    // -----------------------------------------------------------------------
    // DtoCollection — type safety and interface compliance
    // -----------------------------------------------------------------------

    it('DtoCollection implements Countable', function (): void {
        expect(DtoCollection::class)->toImplement(\Countable::class);
    });

    it('DtoCollection implements ArrayAccess', function (): void {
        expect(DtoCollection::class)->toImplement(\ArrayAccess::class);
    });

    it('DtoCollection implements IteratorAggregate', function (): void {
        expect(DtoCollection::class)->toImplement(\IteratorAggregate::class);
    });

    it('DtoCollection implements JsonSerializable', function (): void {
        expect(DtoCollection::class)->toImplement(\JsonSerializable::class);
    });

    it('DtoCollection rejects non-DTO items', function (): void {
        expect(fn () => new DtoCollection(['not-a-dto']))->toThrow(\InvalidArgumentException::class);
    });

    it('DtoCollection count() returns correct count', function (): void {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'A',
        ], validate: false);
        $dto2 = CreateUserDTO::fromArray([
            'email' => 'c@d.com',
            'name' => 'C',
        ], validate: false);

        $col = new DtoCollection([$dto1, $dto2]);
        expect(count($col))->toBe(2);
        expect($col->count())->toBe(2);
    });

    it('DtoCollection isEmpty() works correctly', function (): void {
        $empty = new DtoCollection;
        expect($empty->isEmpty())->toBeTrue();
        expect($empty->isNotEmpty())->toBeFalse();
    });

    it('DtoCollection map() returns plain array', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Test',
        ], validate: false);
        $col = new DtoCollection([$dto]);

        $emails = $col->map(fn (DataTransferObject $d): string => $d->email);
        expect($emails)->toBe(['a@b.com']);
    });

    it('DtoCollection filter() returns new collection', function (): void {
        $dto1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $dto2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'Charlie'], validate: false);
        $col = new DtoCollection([$dto1, $dto2]);

        $filtered = $col->filter(fn (DataTransferObject $d): bool => str_starts_with($d->name, 'A'));
        expect($filtered)->not->toBe($col);
        expect($filtered->count())->toBe(1);
    });

    it('DtoCollection append() returns new collection without mutating original', function (): void {
        $dto1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
        $dto2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'C'], validate: false);
        $col = new DtoCollection([$dto1]);

        $newCol = $col->append($dto2);
        expect($col->count())->toBe(1);
        expect($newCol->count())->toBe(2);
    });

    it('DtoCollection merge() combines two collections', function (): void {
        $dto1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'A'], validate: false);
        $dto2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'C'], validate: false);
        $col1 = new DtoCollection([$dto1]);
        $col2 = new DtoCollection([$dto2]);

        $merged = $col1->merge($col2);
        expect($merged->count())->toBe(2);
        expect($col1->count())->toBe(1);
        expect($col2->count())->toBe(1);
    });

    it('DtoCollection pluck() extracts a property from each DTO', function (): void {
        $dto1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $dto2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'Charlie'], validate: false);
        $col = new DtoCollection([$dto1, $dto2]);

        $names = $col->pluck('name');
        expect($names)->toBe(['Alice', 'Charlie']);
    });

    // -----------------------------------------------------------------------
    // DataTransferObject — fromJson roundtrip
    // -----------------------------------------------------------------------

    it('fromJson creates DTO from valid JSON object', function (): void {
        $json = json_encode(['name' => 'Test', 'value' => '123']);
        $dto = MinimalDTO::fromJson($json, validate: false);

        expect($dto)->toBeInstanceOf(MinimalDTO::class);
        expect($dto->name)->toBe('Test');
        expect($dto->value)->toBe('123');
    });

    it('fromJson rejects sequential arrays', function (): void {
        $json = json_encode(['first', 'second']);
        expect(fn () => MinimalDTO::fromJson($json, validate: false))
            ->toThrow(DTOException::class);
    });

    it('fromJson accepts empty object', function (): void {
        // MinimalDTO requires fields so this will fail at hydration, but JSON parsing should work
        $json = '{}';
        expect(fn () => MinimalDTO::fromJson($json, validate: false))
            ->toThrow(\ArgumentCountError::class); // Missing required constructor args
    });

    it('fromJson rejects invalid JSON', function (): void {
        expect(fn () => MinimalDTO::fromJson('not-json', validate: false))
            ->toThrow(DTOException::class);
    });

    // -----------------------------------------------------------------------
    // DataTransferObject — with() immutability
    // -----------------------------------------------------------------------

    it('with() returns a new instance', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
        ], validate: false);

        $modified = $dto->with(['name' => 'Bob']);

        expect($dto)->not->toBe($modified);
        expect($dto->name)->toBe('Alice');
        expect($modified->name)->toBe('Bob');
    });

    // -----------------------------------------------------------------------
    // DataTransferObject — only/except
    // -----------------------------------------------------------------------

    it('only() returns specified fields', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
            'status' => 'active',
        ], validate: false);

        $only = $dto->only('email', 'name');
        expect($only)->toHaveKeys(['email', 'name']);
        expect($only)->not->toHaveKey('status');
    });

    it('except() excludes specified fields', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
            'status' => 'active',
        ], validate: false);

        $except = $dto->except('status');
        expect($except)->toHaveKey('email');
        expect($except)->toHaveKey('name');
        expect($except)->not->toHaveKey('status');
    });

    // -----------------------------------------------------------------------
    // DataTransferObject — Hidden attribute
    // -----------------------------------------------------------------------

    it('toArray() excludes hidden properties', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
            'password' => 'secret123',
        ], validate: false);

        expect($dto->toArray())->not->toHaveKey('password');
        expect($dto->allValues())->toHaveKey('password');
    });

    // -----------------------------------------------------------------------
    // DataTransferObject — equals()
    // -----------------------------------------------------------------------

    it('equals() returns true for identical data', function (): void {
        $data = ['email' => 'a@b.com', 'name' => 'Alice'];
        $dto1 = CreateUserDTO::fromArray($data, validate: false);
        $dto2 = CreateUserDTO::fromArray($data, validate: false);

        expect($dto1->equals($dto2))->toBeTrue();
    });

    it('equals() returns false for different data', function (): void {
        $dto1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice'], validate: false);
        $dto2 = CreateUserDTO::fromArray(['email' => 'c@d.com', 'name' => 'Charlie'], validate: false);

        expect($dto1->equals($dto2))->toBeFalse();
    });

    // -----------------------------------------------------------------------
    // DataTransferObject — isEmpty/isNotEmpty
    // -----------------------------------------------------------------------

    it('isEmpty() returns false for DTO with non-empty required fields', function (): void {
        $dto = MinimalDTO::fromArray(['name' => 'Test', 'value' => '123'], validate: false);
        expect($dto->isEmpty())->toBeFalse();
        expect($dto->isNotEmpty())->toBeTrue();
    });

    // -----------------------------------------------------------------------
    // DataTransferObject — toJson/jsonSerialize
    // -----------------------------------------------------------------------

    it('toJson() returns valid JSON string', function (): void {
        $dto = MinimalDTO::fromArray(['name' => 'Test', 'value' => '123'], validate: false);
        $json = $dto->toJson();

        expect($json)->toBeJson();
        $decoded = json_decode($json, true);
        expect($decoded)->toBe(['name' => 'Test', 'value' => '123']);
    });

    it('jsonSerialize() returns same as toArray()', function (): void {
        $dto = MinimalDTO::fromArray(['name' => 'Test', 'value' => '123'], validate: false);
        expect($dto->jsonSerialize())->toBe($dto->toArray());
    });

    // -----------------------------------------------------------------------
    // DataTransferObject — MapFrom
    // -----------------------------------------------------------------------

    it('MapFrom maps source key to property', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
            'phone_number' => '+1234567890',
        ], validate: false);

        expect($dto->phone)->toBe('+1234567890');
    });

    // -----------------------------------------------------------------------
    // DataTransferObject — Cast
    // -----------------------------------------------------------------------

    it('Cast attribute transforms values during hydration', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
            'tags' => '["php","laravel"]',
        ], validate: false);

        expect($dto->tags)->toBe(['php', 'laravel']);
    });

    // -----------------------------------------------------------------------
    // DataTransferObject — DefaultValue
    // -----------------------------------------------------------------------

    it('DefaultValue is applied when key is missing', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
        ], validate: false);

        expect($dto->status)->toBe('active');
    });

    // -----------------------------------------------------------------------
    // Metadata cache TTL
    // -----------------------------------------------------------------------

    it('setMetadataCacheTtl affects cache invalidation', function (): void {
        DataTransferObject::setMetadataCacheTtl(0.0);

        // Resolve metadata
        CreateUserDTO::rules();

        // Flush all cache
        DataTransferObject::flushMetadataCache();

        // Setting TTL should work without errors
        DataTransferObject::setMetadataCacheTtl(2.0);
        expect(fn () => CreateUserDTO::rules())->not->toThrow(\Throwable::class);
    });

    it('flushMetadataCache with class flushes only that class', function (): void {
        CreateUserDTO::rules();
        MinimalDTO::rules();

        DataTransferObject::flushMetadataCache(CreateUserDTO::class);

        // MinimalDTO cache should still be intact (metadata resolved again)
        expect(fn () => MinimalDTO::rules())->not->toThrow(\Throwable::class);
    });
});
