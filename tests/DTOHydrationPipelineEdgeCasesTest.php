<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;
use ZeroBoiler\DTO\Tests\Fixtures\MinimalDTO;

describe('DTO hydration pipeline edge cases', function () {
    describe('fromArray with extra keys', function () {
        it('ignores keys not defined in the DTO', function () {
            $dto = MinimalDTO::fromArray([
                'name' => 'Test',
                'value' => 'val',
                'undefined_key' => 'should_be_ignored',
            ], validate: false);

            expect($dto->name)->toBe('Test');
            expect($dto->value)->toBe('val');
        });
    });

    describe('with() always validates', function () {
        it('creates new instance with merged data', function () {
            $dto = MinimalDTO::fromArray(['name' => 'Test', 'value' => 'a'], validate: false);

            $updated = $dto->with(['name' => 'Updated']);

            expect($updated->name)->toBe('Updated');
            expect($updated->value)->toBe('a');
            expect($updated)->not->toBe($dto);
        });

        it('original DTO is unchanged after with()', function () {
            $dto = MinimalDTO::fromArray(['name' => 'Test', 'value' => 'a'], validate: false);

            $dto->with(['name' => 'Updated']);

            expect($dto->name)->toBe('Test');
        });
    });

    describe('only() and except() with edge cases', function () {
        it('only() with single string key', function () {
            $dto = MinimalDTO::fromArray(['name' => 'Test', 'value' => 'a'], validate: false);

            $result = $dto->only('name');

            expect($result)->toBe(['name' => 'Test']);
        });

        it('only() with non-existent key returns empty for that key', function () {
            $dto = MinimalDTO::fromArray(['name' => 'Test', 'value' => 'a'], validate: false);

            $result = $dto->only('nonexistent');

            expect($result)->toBe([]);
        });

        it('except() with string key', function () {
            $dto = MinimalDTO::fromArray(['name' => 'Test', 'value' => 'a'], validate: false);

            $result = $dto->except('name');

            expect($result)->toBe(['value' => 'a']);
        });

        it('except() with non-existent key returns all fields', function () {
            $dto = MinimalDTO::fromArray(['name' => 'Test', 'value' => 'a'], validate: false);

            $result = $dto->except('nonexistent');

            expect($result)->toBe(['name' => 'Test', 'value' => 'a']);
        });
    });

    describe('toArray vs allValues', function () {
        it('both return same output when no hidden fields', function () {
            $dto = MinimalDTO::fromArray(['name' => 'Test', 'value' => 'a'], validate: false);

            expect($dto->toArray())->toBe($dto->allValues());
        });
    });

    describe('isEmpty and isNotEmpty', function () {
        it('isNotEmpty returns true when properties have values', function () {
            $dto = MinimalDTO::fromArray(['name' => 'Test', 'value' => 'a'], validate: false);

            expect($dto->isEmpty())->toBeFalse();
            expect($dto->isNotEmpty())->toBeTrue();
        });
    });

    describe('equals', function () {
        it('returns true for DTOs with same values', function () {
            $a = MinimalDTO::fromArray(['name' => 'Test', 'value' => 'a'], validate: false);
            $b = MinimalDTO::fromArray(['name' => 'Test', 'value' => 'a'], validate: false);

            expect($a->equals($b))->toBeTrue();
        });

        it('returns false for DTOs with different values', function () {
            $a = MinimalDTO::fromArray(['name' => 'Test', 'value' => 'a'], validate: false);
            $b = MinimalDTO::fromArray(['name' => 'Other', 'value' => 'b'], validate: false);

            expect($a->equals($b))->toBeFalse();
        });
    });

    describe('toJson', function () {
        it('returns valid JSON string', function () {
            $dto = MinimalDTO::fromArray(['name' => 'Test', 'value' => 'a'], validate: false);

            $json = $dto->toJson();

            expect($json)->toBeJson();
            $decoded = json_decode($json, true);
            expect($decoded['name'])->toBe('Test');
        });

        it('accepts JSON encoding options', function () {
            $dto = MinimalDTO::fromArray(['name' => 'Test', 'value' => 'a'], validate: false);

            $json = $dto->toJson(JSON_PRETTY_PRINT);

            expect($json)->toContain("\n");
            expect($json)->toBeJson();
        });
    });

    describe('jsonSerialize', function () {
        it('returns same as toArray', function () {
            $dto = MinimalDTO::fromArray(['name' => 'Test', 'value' => 'a'], validate: false);

            expect($dto->jsonSerialize())->toBe($dto->toArray());
        });
    });

    describe('static rules methods', function () {
        it('rules() returns an array', function () {
            $rules = MinimalDTO::rules();

            expect($rules)->toBeArray();
        });

        it('rulesFor() returns same as rules() by default', function () {
            $rules = MinimalDTO::rules();
            $rulesForCreate = MinimalDTO::rulesFor('create');

            expect($rulesForCreate)->toBe($rules);
        });

        it('rulesFor() with unknown action returns same as rules()', function () {
            $rules = MinimalDTO::rules();
            $rulesForCustom = MinimalDTO::rulesFor('custom_action');

            expect($rulesForCustom)->toBe($rules);
        });
    });

    describe('empty DTO with nullable properties', function () {
        it('creates from empty array using defaults', function () {
            $dto = EmptyDTO::fromArray([], validate: false);

            expect($dto->foo)->toBeNull();
            expect($dto->bar)->toBeNull();
        });

        it('isEmpty returns true when all nullable props are null', function () {
            $dto = EmptyDTO::fromArray([], validate: false);

            expect($dto->isEmpty())->toBeTrue();
        });
    });
});
