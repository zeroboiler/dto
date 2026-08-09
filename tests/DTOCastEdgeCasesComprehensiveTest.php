<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\Attributes\Cast;
use ZeroBoiler\DTO\Attributes\DefaultValue;
use ZeroBoiler\DTO\Attributes\Hidden;
use ZeroBoiler\DTO\Attributes\MapFrom;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\Casts\DTOCast;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Tests\Fixtures\MinimalDTO;

describe('DTOCast Edge Cases', function (): void {
    it('returns null when getting null value', function (): void {
        $cast = new DTOCast(MinimalDTO::class);
        $model = new class {
            public function __construct(public readonly array $attributes = []) {}
        };

        $result = $cast->get($model, 'data', null, []);

        expect($result)->toBeNull();
    });

    it('returns null when getting invalid JSON string', function (): void {
        $cast = new DTOCast(MinimalDTO::class);
        $model = new class {
            public function __construct(public readonly array $attributes = []) {}
        };

        $result = $cast->get($model, 'data', 'not-json', []);

        expect($result)->toBeNull();
    });

    it('returns null when getting non-array non-string value', function (): void {
        $cast = new DTOCast(MinimalDTO::class);
        $model = new class {
            public function __construct(public readonly array $attributes = []) {}
        };

        $result = $cast->get($model, 'data', 123, []);

        expect($result)->toBeNull();
    });

    it('returns null when setting null value', function (): void {
        $cast = new DTOCast(MinimalDTO::class);
        $model = new class {
            public function __construct(public readonly array $attributes = []) {}
        };

        $result = $cast->set($model, 'data', null, []);

        expect($result)->toBeNull();
    });

    it('serializes DTO to array', function (): void {
        $cast = new DTOCast(MinimalDTO::class);
        $model = new class {
            public function __construct(public readonly array $attributes = []) {}
        };

        $dto = new MinimalDTO(name: 'Test', value: 'hello');
        $result = $cast->serialize($model, 'data', $dto, []);

        expect($result)->toBeArray();
        expect($result)->toBe(['name' => 'Test', 'value' => 'hello']);
    });

    it('returns null when serializing null', function (): void {
        $cast = new DTOCast(MinimalDTO::class);
        $model = new class {
            public function __construct(public readonly array $attributes = []) {}
        };

        $result = $cast->serialize($model, 'data', null, []);

        expect($result)->toBeNull();
    });

    it('throws InvalidArgumentException for unsupported type in set', function (): void {
        $cast = new DTOCast(MinimalDTO::class);
        $model = new class {
            public function __construct(public readonly array $attributes = []) {}
        };

        expect(fn (): mixed => $cast->set($model, 'data', 123.45, []))
            ->toThrow(\InvalidArgumentException::class);
    });

    it('accepts DTO instance in set and returns JSON', function (): void {
        $cast = new DTOCast(MinimalDTO::class);
        $model = new class {
            public function __construct(public readonly array $attributes = []) {}
        };

        $dto = new MinimalDTO(name: 'Test', value: 'hello');
        $result = $cast->set($model, 'data', $dto, []);

        expect($result)->toBeString();
        expect(json_decode($result, true))->toBe(['name' => 'Test', 'value' => 'hello']);
    });

    it('accepts array in set and returns JSON', function (): void {
        $cast = new DTOCast(MinimalDTO::class);
        $model = new class {
            public function __construct(public readonly array $attributes = []) {}
        };

        $result = $cast->set($model, 'data', ['name' => 'Test', 'value' => 'hello'], []);

        expect($result)->toBeString();
        expect(json_decode($result, true))->toBe(['name' => 'Test', 'value' => 'hello']);
    });

    it('can be constructed with validate=false flag', function (): void {
        $cast = new DTOCast(MinimalDTO::class, validate: false);
        $model = new class {
            public function __construct(public readonly array $attributes = []) {}
        };

        // Should not throw even with potentially invalid data
        $result = $cast->set($model, 'data', ['name' => 'T', 'value' => 'h'], []);

        expect($result)->toBeString();
    });
});
