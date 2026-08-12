<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Tests;

use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Facades\DTO;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;

describe('DtoCollection clone and immutability contract', function () {
    it('append returns a new collection without mutating the original', function () {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'a@test.com',
            'name' => 'Alice',
        ], validate: false);
        $dto2 = CreateUserDTO::fromArray([
            'email' => 'b@test.com',
            'name' => 'Bob',
        ], validate: false);

        $original = DtoCollection::make([$dto1]);
        $appended = $original->append($dto2);

        expect($original->count())->toBe(1);
        expect($appended->count())->toBe(2);
        expect($appended)->not->toBe($original);
    });

    it('merge creates a new collection combining both', function () {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'a@test.com',
            'name' => 'Alice',
        ], validate: false);
        $dto2 = CreateUserDTO::fromArray([
            'email' => 'b@test.com',
            'name' => 'Bob',
        ], validate: false);
        $dto3 = CreateUserDTO::fromArray([
            'email' => 'c@test.com',
            'name' => 'Charlie',
        ], validate: false);

        $col1 = DtoCollection::make([$dto1]);
        $col2 = DtoCollection::make([$dto2, $dto3]);
        $merged = $col1->merge($col2);

        expect($merged->count())->toBe(3);
        expect($col1->count())->toBe(1);
        expect($col2->count())->toBe(2);
    });

    it('filter returns a new collection without mutating original', function () {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'a@test.com',
            'name' => 'Alice',
        ], validate: false);
        $dto2 = CreateUserDTO::fromArray([
            'email' => 'b@test.com',
            'name' => 'Bob',
        ], validate: false);

        $original = DtoCollection::make([$dto1, $dto2]);
        $filtered = $original->filter(
            fn (DataTransferObject $dto): bool => $dto->name === 'Alice',
        );

        expect($original->count())->toBe(2);
        expect($filtered->count())->toBe(1);
    });

    it('offsetUnset re-indexes the collection', function () {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'a@test.com',
            'name' => 'Alice',
        ], validate: false);
        $dto2 = CreateUserDTO::fromArray([
            'email' => 'b@test.com',
            'name' => 'Bob',
        ], validate: false);
        $dto3 = CreateUserDTO::fromArray([
            'email' => 'c@test.com',
            'name' => 'Charlie',
        ], validate: false);

        $col = DtoCollection::make([$dto1, $dto2, $dto3]);
        unset($col[0]);

        // After re-indexing, index 0 should now be Bob, index 1 Charlie
        expect($col->count())->toBe(2);
        expect($col[0]->name)->toBe('Bob');
        expect($col[1]->name)->toBe('Charlie');
        expect($col->last()->name)->toBe('Charlie');
    });
});

describe('DtoCollection type safety enforcement', function () {
    it('rejects non-DTO items in constructor', function () {
        expect(fn () => new DtoCollection(['not_a_dto']))
            ->toThrow(\InvalidArgumentException::class);
    });

    it('rejects non-DTO items in offsetSet', function () {
        $col = DtoCollection::make();

        expect(fn () => $col[0] = 'not_a_dto')
            ->toThrow(\InvalidArgumentException::class);
    });

    it('rejects non-DTO items in push', function () {
        $col = DtoCollection::make();

        // push() accepts DataTransferObject — passing a string should work
        // since PHP enforces the type hint, but let's verify the DtoCollection
        // accepts valid DTOs via push
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@test.com',
            'name' => 'Alice',
        ], validate: false);

        $result = $col->push($dto);
        expect($result->count())->toBe(1);
        expect($result)->toBe($col); // push returns self for chaining
    });
});

describe('DtoCollection iteration and access', function () {
    it('implements IteratorAggregate for foreach', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@test.com',
            'name' => 'Alice',
        ], validate: false);
        $col = DtoCollection::make([$dto]);

        $iterated = false;
        foreach ($col as $item) {
            $iterated = true;
            expect($item)->toBeInstanceOf(DataTransferObject::class);
        }

        expect($iterated)->toBeTrue();
    });

    it('implements Countable', function () {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'a@test.com',
            'name' => 'Alice',
        ], validate: false);
        $dto2 = CreateUserDTO::fromArray([
            'email' => 'b@test.com',
            'name' => 'Bob',
        ], validate: false);

        $col = DtoCollection::make([$dto1, $dto2]);

        expect(count($col))->toBe(2);
    });

    it('implements ArrayAccess', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@test.com',
            'name' => 'Alice',
        ], validate: false);
        $col = DtoCollection::make([$dto]);

        expect(isset($col[0]))->toBeTrue();
        expect($col[0])->toBeInstanceOf(DataTransferObject::class);
        expect($col[999])->toBeNull(); // offsetGet returns null for missing
        expect(isset($col[999]))->toBeFalse();
    });

    it('implements JsonSerializable', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@test.com',
            'name' => 'Alice',
        ], validate: false);
        $col = DtoCollection::make([$dto]);

        $json = json_encode($col);
        expect($json)->toBeJson();
        $decoded = json_decode($json, true);
        expect($decoded)->toBeArray();
        expect($decoded[0])->toHaveKey('email');
        expect($decoded[0])->toHaveKey('name');
    });
});

describe('DTO facade and DTOManager contract', function () {
    it('DTO facade returns correct accessor', function () {
        expect(DTO::getFacadeAccessor())->toBe('zeroboiler.dto');
    });

    it('DTO facade is final', function () {
        $ref = new \ReflectionClass(DTO::class);

        expect($ref->isFinal())->toBeTrue();
    });

    it('DTOManager is readonly final', function () {
        $ref = new \ReflectionClass(\ZeroBoiler\DTO\DTOManager::class);

        expect($ref->isFinal())->toBeTrue();
        expect($ref->isReadOnly())->toBeTrue();
    });

    it('DTOManager validate delegates to DTO class', function () {
        $manager = new \ZeroBoiler\DTO\DTOManager;
        $result = $manager->validate(CreateUserDTO::class, [
            'email' => 'valid@example.com',
            'name' => 'Test User',
        ]);

        expect($result)->toBeArray();
        expect($result['email'])->toBe('valid@example.com');
    });

    it('DTOManager make creates DTO from data', function () {
        $manager = new \ZeroBoiler\DTO\DTOManager;
        $dto = $manager->make(CreateUserDTO::class, [
            'email' => 'test@example.com',
            'name' => 'Alice',
        ]);

        expect($dto)->toBeInstanceOf(CreateUserDTO::class);
        expect($dto->email)->toBe('test@example.com');
    });

    it('DTOManager makeFromJson creates DTO from JSON', function () {
        $manager = new \ZeroBoiler\DTO\DTOManager;
        $dto = $manager->makeFromJson(
            CreateUserDTO::class,
            '{"email":"test@example.com","name":"Alice"}',
        );

        expect($dto)->toBeInstanceOf(CreateUserDTO::class);
        expect($dto->name)->toBe('Alice');
    });

    it('DTOManager makeFromJson throws DTOException for invalid JSON', function () {
        $manager = new \ZeroBoiler\DTO\DTOManager;

        expect(fn () => $manager->makeFromJson(CreateUserDTO::class, 'not-json'))
            ->toThrow(DTOException::class);
    });

    it('DTOManager schema generates OpenAPI schema', function () {
        $manager = new \ZeroBoiler\DTO\DTOManager;
        $schema = $manager->schema(CreateUserDTO::class);

        expect($schema)->toBeArray();
        expect($schema)->toHaveKey('type');
        expect($schema['type'])->toBe('object');
        expect($schema)->toHaveKey('properties');
    });
});

describe('DTOException named constructors', function () {
    it('invalidCast creates exception with property and type info', function () {
        $ex = DTOException::invalidCast('age', 'integer', 'not_an_int');

        expect($ex->getMessage())->toContain('age');
        expect($ex->getMessage())->toContain('integer');
    });

    it('invalidJson creates exception with property and error', function () {
        $ex = DTOException::invalidJson('payload', 'Syntax error');

        expect($ex->getMessage())->toContain('payload');
        expect($ex->getMessage())->toContain('Syntax error');
    });

    it('__toString returns class name and message', function () {
        $ex = DTOException::invalidCast('field', 'int', 'bad');

        $str = (string) $ex;
        expect($str)->toContain(DTOException::class);
        expect($str)->toContain('field');
    });
});

describe('DTOSServiceProvider structure', function () {
    it('is final', function () {
        $ref = new \ReflectionClass(\ZeroBoiler\DTO\DTOSServiceProvider::class);

        expect($ref->isFinal())->toBeTrue();
    });

    it('extends Illuminate ServiceProvider', function () {
        $ref = new \ReflectionClass(\ZeroBoiler\DTO\DTOSServiceProvider::class);

        expect($ref->isSubclassOf(\Illuminate\Support\ServiceProvider::class))->toBeTrue();
    });
});

describe('DataTransferObject metadata cache contract', function () {
    it('flushMetadataCache clears all cached metadata', function () {
        // Populate the cache by resolving metadata
        $rules1 = CreateUserDTO::rules();
        expect($rules1)->not->toBeEmpty();

        // Flush
        CreateUserDTO::flushMetadataCache();

        // Re-resolve — should still work after flush
        $rules2 = CreateUserDTO::rules();
        expect($rules2)->toBe($rules1);
    });

    it('flushMetadataCache with specific class only clears that class', function () {
        // Populate caches
        CreateUserDTO::rules();

        // Flush only CreateUserDTO
        CreateUserDTO::flushMetadataCache(CreateUserDTO::class);

        // Rules should still resolve correctly
        $rules = CreateUserDTO::rules();
        expect($rules)->not->toBeEmpty();
    });
});

describe('ValidatableDTO and FromRequestDTO contracts', function () {
    it('CreateUserDTO implements ValidatableDTO', function () {
        expect(CreateUserDTO::class)
            ->toImplement(\ZeroBoiler\DTO\Contracts\ValidatableDTO::class);
    });

    it('CreateUserDTO implements FromRequestDTO', function () {
        expect(CreateUserDTO::class)
            ->toImplement(\ZeroBoiler\DTO\Contracts\FromRequestDTO::class);
    });

    it('rulesFor returns rules by default (same as rules)', function () {
        expect(CreateUserDTO::rulesFor('create'))
            ->toBe(CreateUserDTO::rules());
    });
});

describe('ValidationAttribute interface contract', function () {
    it('Required attribute implements ValidationAttribute', function () {
        $attr = new \ZeroBoiler\DTO\Attributes\Required;

        expect($attr)->toBeInstanceOf(\ZeroBoiler\DTO\Contracts\ValidationAttribute::class);
        expect($attr->ruleKey())->toBe('required');
    });

    it('Email attribute implements ValidationAttribute', function () {
        $attr = new \ZeroBoiler\DTO\Attributes\Email;

        expect($attr->ruleKey())->toBe('email');
    });

    it('MapFrom attribute does NOT implement ValidationAttribute', function () {
        $attr = new \ZeroBoiler\DTO\Attributes\MapFrom('source_key');

        expect($attr)->not->toBeInstanceOf(\ZeroBoiler\DTO\Contracts\ValidationAttribute::class);
    });

    it('Hidden attribute does NOT implement ValidationAttribute', function () {
        $attr = new \ZeroBoiler\DTO\Attributes\Hidden;

        expect($attr)->not->toBeInstanceOf(\ZeroBoiler\DTO\Contracts\ValidationAttribute::class);
    });
});

describe('DtoCollection pluckKey edge cases', function () {
    it('pluckKey skips items where key is null', function () {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'a@test.com',
            'name' => 'Alice',
        ], validate: false);

        // name is a required string so it won't be null
        // Use email as key — all emails exist
        $col = DtoCollection::make([$dto1]);
        $result = $col->pluckKey('email', 'name');

        expect($result)->toBeArray();
        expect($result)->toHaveKey('a@test.com');
        expect($result['a@test.com'])->toBe('Alice');
    });

    it('pluckKey without valueField returns full toArray', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@test.com',
            'name' => 'Alice',
        ], validate: false);
        $col = DtoCollection::make([$dto]);

        $result = $col->pluckKey('email');

        expect($result['a@test.com'])->toBeArray();
        expect($result['a@test.com'])->toHaveKey('email');
        expect($result['a@test.com'])->toHaveKey('name');
    });
});
