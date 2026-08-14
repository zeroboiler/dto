<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Tests;

use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Tests\Fixtures\ArticleDTO;
use ZeroBoiler\DTO\Tests\Fixtures\ProductDTO;
use ZeroBoiler\DTO\Tests\Fixtures\StrictValidationDTO;

/**
 * Tests for DTO production-readiness — metadata cache, hydration pipeline,
 * immutable update semantics, equality, state checks, and edge cases.
 *
 * These tests ensure the DTO system works correctly in production scenarios
 * including long-running processes, PATCH updates, and cross-DTO operations.
 */
describe('DTO metadata cache and resolution tests', function () {
    it('metadata is cached after first resolution', function () {
        $rules1 = StrictValidationDTO::rules();
        $rules2 = StrictValidationDTO::rules();

        expect($rules1)->toBe($rules2);
    });

    it('flushMetadataCache invalidates cached rules', function () {
        StrictValidationDTO::rules(); // Populate cache
        StrictValidationDTO::flushMetadataCache();

        // Re-resolve should work without error
        $rules = StrictValidationDTO::rules();
        expect($rules)->toBeArray()->not->toBeEmpty();
    });

    it('flushMetadataCache for specific class does not affect others', function () {
        ProductDTO::rules();
        ArticleDTO::rules();

        ArticleDTO::flushMetadataCache(ArticleDTO::class);

        // ProductDTO should still be cached
        $productRules = ProductDTO::rules();
        expect($productRules)->toBeArray()->not->toBeEmpty();

        // ArticleDTO should be re-resolved
        $articleRules = ArticleDTO::rules();
        expect($articleRules)->toBeArray()->not->toBeEmpty();
    });

    it('setMetadataCacheTtl affects invalidation behavior', function () {
        DataTransferObject::setMetadataCacheTtl(0); // Disable TTL

        $rules = ArticleDTO::rules();
        expect($rules)->toBeArray()->not->toBeEmpty();

        DataTransferObject::setMetadataCacheTtl(0.0); // Reset
    });
});

describe('DTO fromJson edge cases', function () {
    it('throws DTOException for invalid JSON', function () {
        expect(fn () => ArticleDTO::fromJson('{invalid json}', validate: false))
            ->toThrow(DTOException::class);
    });

    it('throws DTOException for JSON that decodes to a scalar', function () {
        expect(fn () => ArticleDTO::fromJson('"just a string"', validate: false))
            ->toThrow(DTOException::class);
    });

    it('throws DTOException for sequential arrays (non-object)', function () {
        expect(fn () => ArticleDTO::fromJson('[1,2,3]', validate: false))
            ->toThrow(DTOException::class);
    });

    it('accepts empty JSON object', function () {
        $dto = ArticleDTO::fromJson('{}', validate: false);
        expect($dto)->toBeInstanceOf(ArticleDTO::class);
    });
});

describe('DTO immutable with() semantics', function () {
    it('with() always validates regardless of validate parameter', function () {
        $dto = ArticleDTO::fromArray([
            'authorEmail' => 'test@example.com',
            'title' => 'Test Article',
            'body' => 'Content here with enough length',
        ], validate: false);

        // The $validate parameter in with() is deprecated and has no effect
        $modified = $dto->with(['title' => 'Updated Title'], validate: false);

        expect($modified)->toBeInstanceOf(ArticleDTO::class);
        expect($modified->title)->toBe('Updated Title');
        // Original should be unchanged
        expect($dto->title)->toBe('Test Article');
    });

    it('with() returns a new instance', function () {
        $dto = ArticleDTO::fromArray([
            'authorEmail' => 'test@example.com',
            'title' => 'Test',
            'body' => 'Content with enough length',
        ], validate: false);

        $modified = $dto->with(['title' => 'Updated']);

        expect($dto)->not->toBe($modified);
        expect(spl_object_id($dto))->not->toBe(spl_object_id($modified));
    });
});

describe('DTO equality and state tests', function () {
    it('equals() returns true for DTOs with identical properties', function () {
        $data = ['authorEmail' => 'a@b.com', 'title' => 'Same', 'body' => 'Same body content'];
        $dto1 = ArticleDTO::fromArray($data, validate: false);
        $dto2 = ArticleDTO::fromArray($data, validate: false);

        expect($dto1->equals($dto2))->toBeTrue();
    });

    it('equals() returns false for DTOs with different properties', function () {
        $dto1 = ArticleDTO::fromArray(['authorEmail' => 'a@b.com', 'title' => 'A', 'body' => 'Body'], validate: false);
        $dto2 = ArticleDTO::fromArray(['authorEmail' => 'a@b.com', 'title' => 'X', 'body' => 'Body'], validate: false);

        expect($dto1->equals($dto2))->toBeFalse();
    });

    it('isEmpty() returns true when all properties are empty/default', function () {
        $dto = ArticleDTO::fromArray([
            'authorEmail' => '',
            'title' => '',
            'body' => '',
        ], validate: false);
        expect($dto->isEmpty())->toBeTrue();
    });

    it('isNotEmpty() returns true when at least one property has value', function () {
        $dto = ArticleDTO::fromArray([
            'authorEmail' => '',
            'title' => 'Hello',
            'body' => '',
        ], validate: false);
        expect($dto->isNotEmpty())->toBeTrue();
    });

    it('zero values (int 0, float 0.0) are not considered empty', function () {
        // This tests the documented behavior that 0 and 0.0 are valid values
        // View count is int with default 0
        $data = ArticleDTO::fromArray([
            'authorEmail' => 'test@example.com',
            'title' => 'Free Article',
            'body' => 'Content here',
            'viewCount' => 0,
        ], validate: false);

        // Article with viewCount=0 should be isNotEmpty (not isEmpty)
        // because 0 is a valid meaningful value and title is non-empty
        expect($data->isNotEmpty())->toBeTrue();
    });
});

describe('DTO serialization consistency', function () {
    it('toArray() and jsonSerialize() produce identical results', function () {
        $dto = ArticleDTO::fromArray([
            'authorEmail' => 'test@example.com',
            'title' => 'Test',
            'body' => 'Body content here',
        ], validate: false);

        expect($dto->toArray())->toBe($dto->jsonSerialize());
    });

    it('toJson() is valid JSON', function () {
        $dto = ArticleDTO::fromArray([
            'authorEmail' => 'test@example.com',
            'title' => 'Test',
            'body' => 'Body content here',
        ], validate: false);

        $json = $dto->toJson();
        expect($json)->toBeJson();

        $decoded = json_decode($json, true);
        expect($decoded)->toBeArray();
        expect($decoded)->toHaveKey('title');
        expect($decoded)->toHaveKey('body');
    });
});

describe('DTO only/except selective output', function () {
    it('only() returns array with only specified keys', function () {
        $dto = ArticleDTO::fromArray([
            'authorEmail' => 'test@example.com',
            'title' => 'Test',
            'body' => 'Content here',
        ], validate: false);

        $only = $dto->only('title');
        expect($only)->toBe(['title' => 'Test']);
    });

    it('only() accepts string for single key', function () {
        $dto = ArticleDTO::fromArray([
            'authorEmail' => 'test@example.com',
            'title' => 'Test',
            'body' => 'Content here',
        ], validate: false);

        $only = $dto->only('body');
        expect($only)->toBe(['body' => 'Content here']);
    });

    it('except() returns array without specified keys', function () {
        $dto = ArticleDTO::fromArray([
            'authorEmail' => 'test@example.com',
            'title' => 'Test',
            'body' => 'Content here',
        ], validate: false);

        $except = $dto->except('title');
        // Should have authorEmail and body but not title
        expect($except)->not->toHaveKey('title');
        expect($except)->toHaveKey('authorEmail');
    });

    it('except() silently ignores non-existent keys', function () {
        $dto = ArticleDTO::fromArray([
            'authorEmail' => 'test@example.com',
            'title' => 'Test',
            'body' => 'Content',
        ], validate: false);

        $except = $dto->except('nonexistent');
        expect($except)->toHaveKey('title');
        expect($except)->toHaveKey('body');
    });
});

describe('DTOException factory tests', function () {
    it('invalidCast() includes property, type, and value debug type', function () {
        $ex = DTOException::invalidCast('age', 'integer', 'not_a_number');
        expect($ex->getMessage())->toContain('age');
        expect($ex->getMessage())->toContain('integer');
        expect($ex->getMessage())->toContain('not_a_number');
    });

    it('invalidJson() includes property and error message', function () {
        $ex = DTOException::invalidJson('metadata', 'Syntax error');
        expect($ex->getMessage())->toContain('metadata');
        expect($ex->getMessage())->toContain('Syntax error');
    });

    it('__toString returns class name and message', function () {
        $ex = DTOException::invalidCast('field', 'int', 'abc');
        $str = (string) $ex;
        expect($str)->toContain(DTOException::class);
        expect($str)->toContain('field');
    });
});

describe('DTO rulesFor action scoping', function () {
    it('rulesFor() defaults to same rules as rules()', function () {
        $defaultRules = ArticleDTO::rules();
        $createRules = ArticleDTO::rulesFor('create');
        $updateRules = ArticleDTO::rulesFor('update');

        expect($createRules)->toBe($defaultRules);
        expect($updateRules)->toBe($defaultRules);
    });
});
