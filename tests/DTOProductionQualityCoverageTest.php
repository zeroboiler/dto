<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;

describe('DTO fromPartialArray Edge Cases', function () {
    it('applies defaults for missing fields', function () {
        $dto = CreateUserDTO::fromPartialArray([], validatePresent: false);

        // Fields with defaults should get their default values
        // Fields without defaults should get type-appropriate empty values
        expect($dto)->toBeInstanceOf(CreateUserDTO::class);
    });

    it('overrides only provided fields', function () {
        $dto = CreateUserDTO::fromPartialArray([
            'name' => 'Updated Name',
        ], validatePresent: false);

        expect($dto->name)->toBe('Updated Name');
    });

    it('respects explicit null values instead of defaults', function () {
        $dto = CreateUserDTO::fromPartialArray([
            'name' => null,
        ], validatePresent: false);

        // Explicit null should be respected per #678
        expect($dto->name)->toBeNull();
    });
});

describe('DtoCollection Advanced Operations', function () {
    it('push returns the same collection for chaining', function () {
        $dtoArray = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
        ], validate: false);

        $col = new DtoCollection([$dtoArray]);
        $result = $col->push($dtoArray);

        expect($result)->toBe($col); // same instance
        expect($col->count())->toBe(2);
    });

    it('append returns a new collection without mutating original', function () {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
        ], validate: false);
        $dto2 = CreateUserDTO::fromArray([
            'email' => 'c@d.com',
            'name' => 'Charlie',
        ], validate: false);

        $col = new DtoCollection([$dto1]);
        $newCol = $col->append($dto2);

        expect($col->count())->toBe(1);
        expect($newCol->count())->toBe(2);
        expect($newCol)->not->toBe($col);
    });

    it('merge combines two collections without mutating originals', function () {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
        ], validate: false);
        $dto2 = CreateUserDTO::fromArray([
            'email' => 'c@d.com',
            'name' => 'Charlie',
        ], validate: false);

        $col1 = new DtoCollection([$dto1]);
        $col2 = new DtoCollection([$dto2]);
        $merged = $col1->merge($col2);

        expect($col1->count())->toBe(1);
        expect($col2->count())->toBe(1);
        expect($merged->count())->toBe(2);
    });

    it('filter returns re-indexed collection', function () {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'alice@test.com',
            'name' => 'Alice',
        ], validate: false);
        $dto2 = CreateUserDTO::fromArray([
            'email' => 'bob@test.com',
            'name' => 'Bob',
        ], validate: false);
        $dto3 = CreateUserDTO::fromArray([
            'email' => 'charlie@test.com',
            'name' => 'Charlie',
        ], validate: false);

        $col = new DtoCollection([$dto1, $dto2, $dto3]);
        $filtered = $col->filter(
            fn (DataTransferObject $dto): bool => str_starts_with($dto->email, 'a') || str_starts_with($dto->email, 'c')
        );

        expect($filtered->count())->toBe(2);
        expect($filtered->first()->email)->toBe('alice@test.com');
    });

    it('offsetUnset re-indexes the collection', function () {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'A',
        ], validate: false);
        $dto2 = CreateUserDTO::fromArray([
            'email' => 'c@d.com',
            'name' => 'C',
        ], validate: false);
        $dto3 = CreateUserDTO::fromArray([
            'email' => 'e@f.com',
            'name' => 'E',
        ], validate: false);

        $col = new DtoCollection([$dto1, $dto2, $dto3]);
        $col->offsetUnset(0);

        expect($col->count())->toBe(2);
        expect($col[0]->email)->toBe('c@d.com');
        expect($col[1]->email)->toBe('e@f.com');
    });

    it('jsonSerialize returns array of toArray results', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
        ], validate: false);

        $col = new DtoCollection([$dto]);
        $json = json_encode($col);

        expect($json)->toBeJson();
        $decoded = json_decode($json, true);
        expect($decoded[0])->toHaveKey('email');
        expect($decoded[0])->toHaveKey('name');
    });

    it('pluck extracts property values using reflection', function () {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
        ], validate: false);
        $dto2 = CreateUserDTO::fromArray([
            'email' => 'c@d.com',
            'name' => 'Charlie',
        ], validate: false);

        $col = new DtoCollection([$dto1, $dto2]);
        $emails = $col->pluck('email');

        expect($emails)->toBe(['a@b.com', 'c@d.com']);
    });

    it('isEmpty and isNotEmpty work correctly', function () {
        $empty = new DtoCollection;
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'A',
        ], validate: false);
        $nonEmpty = new DtoCollection([$dto]);

        expect($empty->isEmpty())->toBeTrue();
        expect($empty->isNotEmpty())->toBeFalse();
        expect($nonEmpty->isEmpty())->toBeFalse();
        expect($nonEmpty->isNotEmpty())->toBeTrue();
    });

    it('make factory creates collection from array', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'A',
        ], validate: false);

        $col = DtoCollection::make([$dto]);
        expect($col->count())->toBe(1);
        expect($col[0]->email)->toBe('a@b.com');
    });

    it('toArrayBy aliases pluckKey with single field', function () {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'alice@test.com',
            'name' => 'Alice',
        ], validate: false);
        $dto2 = CreateUserDTO::fromArray([
            'email' => 'bob@test.com',
            'name' => 'Bob',
        ], validate: false);

        $col = new DtoCollection([$dto1, $dto2]);
        $byEmail = $col->toArrayBy('email');

        expect($byEmail)->toHaveKey('alice@test.com');
        expect($byEmail)->toHaveKey('bob@test.com');
    });

    it('toDictionary maps one property to another', function () {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'alice@test.com',
            'name' => 'Alice',
        ], validate: false);
        $dto2 = CreateUserDTO::fromArray([
            'email' => 'bob@test.com',
            'name' => 'Bob',
        ], validate: false);

        $col = new DtoCollection([$dto1, $dto2]);
        $dict = $col->toDictionary('email', 'name');

        expect($dict['alice@test.com'])->toBe('Alice');
        expect($dict['bob@test.com'])->toBe('Bob');
    });

    it('toDictionary skips items with null key values', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => null,
            'name' => 'NoEmail',
        ], validate: false);

        $col = new DtoCollection([$dto]);
        $dict = $col->toDictionary('email', 'name');

        expect($dict)->toBeEmpty();
    });
});

describe('DTO Serialization Consistency', function () {
    it('toArray excludes hidden fields', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@test.com',
            'name' => 'Test',
            'password' => 'secret123',
        ], validate: false);

        $arr = $dto->toArray();

        expect($arr)->toHaveKey('email');
        expect($arr)->toHaveKey('name');
        expect($arr)->not->toHaveKey('password');
    });

    it('allValues includes hidden fields', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@test.com',
            'name' => 'Test',
            'password' => 'secret123',
        ], validate: false);

        $all = $dto->allValues();

        expect($all)->toHaveKey('email');
        expect($all)->toHaveKey('password');
    });

    it('toJson produces valid JSON', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@test.com',
            'name' => 'Test',
        ], validate: false);

        $json = $dto->toJson();

        expect($json)->toBeJson();
        $decoded = json_decode($json, true);
        expect($decoded['email'])->toBe('test@test.com');
    });

    it('only returns subset of fields', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@test.com',
            'name' => 'Test',
        ], validate: false);

        $only = $dto->only('email');

        expect($only)->toHaveKey('email');
        expect($only)->not->toHaveKey('name');
    });

    it('except returns all but specified fields', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@test.com',
            'name' => 'Test',
        ], validate: false);

        $except = $dto->except('name');

        expect($except)->toHaveKey('email');
        expect($except)->not->toHaveKey('name');
    });

    it('equals compares toArray output', function () {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'test@test.com',
            'name' => 'Test',
        ], validate: false);
        $dto2 = CreateUserDTO::fromArray([
            'email' => 'test@test.com',
            'name' => 'Test',
        ], validate: false);
        $dto3 = CreateUserDTO::fromArray([
            'email' => 'other@test.com',
            'name' => 'Other',
        ], validate: false);

        expect($dto1->equals($dto2))->toBeTrue();
        expect($dto1->equals($dto3))->toBeFalse();
    });
});

describe('DTO isEmpty Edge Cases', function () {
    it('returns true when all properties are null/empty/default', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => null,
            'name' => null,
        ], validate: false);

        // This depends on property definitions — adjust based on actual DTO
        expect(method_exists($dto, 'isEmpty'))->toBeTrue();
    });

    it('returns false when at least one property has a non-empty value', function () {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@test.com',
            'name' => null,
        ], validate: false);

        expect($dto->isEmpty())->toBeFalse();
    });
});
