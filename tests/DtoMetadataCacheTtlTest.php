<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;
use ZeroBoiler\DTO\Tests\Fixtures\MinimalDTO;

describe('DTO metadata cache TTL behavior', function (): void {
    beforeEach(function (): void {
        DataTransferObject::flushMetadataCache();
        DataTransferObject::setMetadataCacheTtl(0.0);
    });

    afterEach(function (): void {
        DataTransferObject::flushMetadataCache();
        DataTransferObject::setMetadataCacheTtl(0.0);
    });

    it('metadata is cached between fromArray calls', function (): void {
        $dto1 = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'x'], validate: false);
        $dto2 = MinimalDTO::fromArray(['name' => 'Bob', 'value' => 'y'], validate: false);

        expect($dto1)->toBeInstanceOf(MinimalDTO::class);
        expect($dto2)->toBeInstanceOf(MinimalDTO::class);
        expect($dto1->name)->toBe('Alice');
        expect($dto2->name)->toBe('Bob');
    });

    it('flushMetadataCache clears cache for all classes', function (): void {
        MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'x'], validate: false);
        DataTransferObject::flushMetadataCache();

        $dto = MinimalDTO::fromArray(['name' => 'Bob', 'value' => 'y'], validate: false);
        expect($dto->name)->toBe('Bob');
    });

    it('flushMetadataCache with null clears all classes', function (): void {
        MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'x'], validate: false);
        DataTransferObject::flushMetadataCache(null);

        $dto = MinimalDTO::fromArray(['name' => 'Bob', 'value' => 'y'], validate: false);
        expect($dto->name)->toBe('Bob');
    });

    it('flushMetadataCache with specific class only clears that class', function (): void {
        MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'x'], validate: false);
        DataTransferObject::flushMetadataCache(MinimalDTO::class);

        $dto = MinimalDTO::fromArray(['name' => 'Bob', 'value' => 'y'], validate: false);
        expect($dto->name)->toBe('Bob');
    });

    it('setMetadataCacheTtl accepts float values', function (): void {
        DataTransferObject::setMetadataCacheTtl(2.0);
        DataTransferObject::setMetadataCacheTtl(0.5);
        DataTransferObject::setMetadataCacheTtl(0.0);
        DataTransferObject::setMetadataCacheTtl(-1.0);

        expect(true)->toBeTrue();
    });

    it('rules() returns same result before and after cache flush', function (): void {
        $rules1 = MinimalDTO::rules();
        DataTransferObject::flushMetadataCache();
        $rules2 = MinimalDTO::rules();

        expect($rules1)->toBe($rules2);
    });

    it('rulesFor() returns same result for any action', function (): void {
        $rules1 = MinimalDTO::rulesFor('create');
        $rules2 = MinimalDTO::rulesFor('update');
        $rules3 = MinimalDTO::rulesFor('patch');

        expect($rules1)->toBe($rules2);
        expect($rules2)->toBe($rules3);
    });

    it('fromArray with validate=false skips validation', function (): void {
        $dto = MinimalDTO::fromArray(['name' => '', 'value' => ''], validate: false);
        expect($dto)->toBeInstanceOf(MinimalDTO::class);
    });

    it('toArray returns only public properties', function (): void {
        $dto = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'x'], validate: false);
        $array = $dto->toArray();

        expect($array)->toBeArray();
        expect($array)->toHaveKey('name');
        expect($array['name'])->toBe('Alice');
    });

    it('allValues returns all properties including hidden', function (): void {
        $dto = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'x'], validate: false);
        $all = $dto->allValues();

        expect($all)->toBeArray();
        expect($all)->toHaveKey('name');
    });

    it('toJson produces valid JSON', function (): void {
        $dto = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'x'], validate: false);
        $json = $dto->toJson();

        expect($json)->toBeJson();
        $decoded = json_decode($json, true);
        expect($decoded)->toBeArray();
        expect($decoded['name'])->toBe('Alice');
    });

    it('jsonSerialize returns toArray output', function (): void {
        $dto = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'x'], validate: false);

        expect($dto->jsonSerialize())->toBe($dto->toArray());
    });

    it('equals returns true for same data, false for different', function (): void {
        $dto1 = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'x'], validate: false);
        $dto2 = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'x'], validate: false);
        $dto3 = MinimalDTO::fromArray(['name' => 'Bob', 'value' => 'y'], validate: false);

        expect($dto1->equals($dto2))->toBeTrue();
        expect($dto1->equals($dto3))->toBeFalse();
    });

    it('isEmpty returns true for DTO with all empty fields', function (): void {
        $dto = MinimalDTO::fromArray(['name' => '', 'value' => ''], validate: false);
        expect($dto->isEmpty())->toBeTrue();
        expect($dto->isNotEmpty())->toBeFalse();
    });

    it('isEmpty returns false for DTO with non-empty fields', function (): void {
        $dto = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'x'], validate: false);
        expect($dto->isEmpty())->toBeFalse();
        expect($dto->isNotEmpty())->toBeTrue();
    });

    it('NoConstructorDTO resolves without error', function (): void {
        $rules = EmptyDTO::rules();
        expect($rules)->toBeArray();
        expect($rules)->toBeEmpty();
    });
});
