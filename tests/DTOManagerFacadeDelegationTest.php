<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Facades\DTO;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;

describe('DTOManager facade delegation — fromJson, fromPartialArray, fromPartialRequest', function () {
    it('delegates fromJson() via facade', function () {
        $json = '{"email":"test@example.com","name":"Alice"}';
        $dto = DTO::fromJson(CreateUserDTO::class, $json);
        expect($dto)->toBeInstanceOf(CreateUserDTO::class);
        expect($dto->email)->toBe('test@example.com');
        expect($dto->name)->toBe('Alice');
    });

    it('fromJson() throws DTOException on invalid JSON', function () {
        DTO::fromJson(CreateUserDTO::class, 'not-json');
    })->throws(DTOException::class);

    it('fromJson() throws DTOException on sequential array JSON', function () {
        DTO::fromJson(CreateUserDTO::class, '["email","test@example.com"]');
    })->throws(DTOException::class);

    it('delegates fromPartialArray() via facade', function () {
        $dto = DTO::fromPartialArray(CreateUserDTO::class, [
            'name' => 'Updated Name',
        ], validate: false);
        expect($dto)->toBeInstanceOf(CreateUserDTO::class);
        expect($dto->name)->toBe('Updated Name');
        expect($dto->status)->toBe('active'); // default value
    });

    it('fromPartialArray() with empty data returns defaults', function () {
        $dto = CreateUserDTO::fromPartialArray([], validate: false);
        expect($dto)->toBeInstanceOf(CreateUserDTO::class);
        expect($dto->status)->toBe('active');
        expect($dto->tags)->toBe([]);
    });

    it('fromPartialArray() returns same result as static call', function () {
        $data = ['name' => 'Bob'];
        $facadeResult = DTO::fromPartialArray(CreateUserDTO::class, $data, validate: false);
        $staticResult = CreateUserDTO::fromPartialArray($data, validate: false);
        expect($facadeResult->toArray())->toBe($staticResult->toArray());
    });

    it('fromJson() returns same result as static call', function () {
        $json = '{"email":"a@b.com","name":"Charlie"}';
        $facadeResult = DTO::fromJson(CreateUserDTO::class, $json, validate: false);
        $staticResult = CreateUserDTO::fromJson($json, validate: false);
        expect($facadeResult->toArray())->toBe($staticResult->toArray());
    });

    it('all facade methods are callable', function () {
        expect(fn () => DTO::validate(CreateUserDTO::class, ['email' => 'a@b.com', 'name' => 'A']))
            ->not->toThrow(\BadMethodCallException::class);
        expect(fn () => DTO::make(CreateUserDTO::class, ['email' => 'a@b.com', 'name' => 'A']))
            ->not->toThrow(\BadMethodCallException::class);
        expect(fn () => DTO::makeFromJson(CreateUserDTO::class, '{"email":"a@b.com","name":"A"}'))
            ->not->toThrow(\BadMethodCallException::class);
        expect(fn () => DTO::fromJson(CreateUserDTO::class, '{"email":"a@b.com","name":"A"}', validate: false))
            ->not->toThrow(\BadMethodCallException::class);
        expect(fn () => DTO::fromPartialArray(CreateUserDTO::class, [], validate: false))
            ->not->toThrow(\BadMethodCallException::class);
        expect(fn () => DTO::rules(CreateUserDTO::class))
            ->not->toThrow(\BadMethodCallException::class);
        expect(fn () => DTO::rulesFor(CreateUserDTO::class, 'create'))
            ->not->toThrow(\BadMethodCallException::class);
        expect(fn () => DTO::schema(CreateUserDTO::class))
            ->not->toThrow(\BadMethodCallException::class);
    });
});
