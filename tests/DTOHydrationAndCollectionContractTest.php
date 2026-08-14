<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Tests\Fixtures\ArticleDTO;
use ZeroBoiler\DTO\Tests\Fixtures\ArticleStatus;
use ZeroBoiler\DTO\Tests\Fixtures\Currency;
use ZeroBoiler\DTO\Tests\Fixtures\MinimalDTO;
use ZeroBoiler\DTO\Tests\Fixtures\ProductDTO;

describe('DTO hydration pipeline contract', function () {
    it('fromArray with validate=false skips validation', function () {
        $dto = ArticleDTO::fromArray([
            'authorEmail' => 'not-email',
            'title' => '',
            'body' => 'x',
        ], validate: false);

        expect($dto)->toBeInstanceOf(ArticleDTO::class);
    });

    it('toArray excludes hidden properties', function () {
        $dto = ArticleDTO::fromArray([
            'authorEmail' => 'test@example.com',
            'title' => 'Test Article',
            'body' => 'Article body content here',
            'internalNote' => 'should-be-hidden',
        ], validate: false);

        $array = $dto->toArray();

        expect($array)->not->toHaveKey('internalNote');
    });

    it('allValues includes hidden properties', function () {
        $dto = ArticleDTO::fromArray([
            'authorEmail' => 'test@example.com',
            'title' => 'Test',
            'body' => 'Body content here for the article',
            'internalNote' => 'secret-note',
        ], validate: false);

        $all = $dto->allValues();

        expect($all)->toHaveKey('internalNote');
        expect($all['internalNote'])->toBe('secret-note');
    });

    it('equals returns true for identical DTOs', function () {
        $data = [
            'authorEmail' => 'test@example.com',
            'title' => 'Title',
            'body' => 'Body content',
        ];
        $dto1 = ArticleDTO::fromArray($data, validate: false);
        $dto2 = ArticleDTO::fromArray($data, validate: false);

        expect($dto1->equals($dto2))->toBeTrue();
    });

    it('equals returns false for different DTOs', function () {
        $dto1 = ArticleDTO::fromArray([
            'authorEmail' => 'a@test.com',
            'title' => 'Title A',
            'body' => 'Body content A is longer',
        ], validate: false);

        $dto2 = ArticleDTO::fromArray([
            'authorEmail' => 'b@test.com',
            'title' => 'Title B',
            'body' => 'Body content B is longer',
        ], validate: false);

        expect($dto1->equals($dto2))->toBeFalse();
    });

    it('isEmpty returns true for DTO with only empty/default values', function () {
        $dto = MinimalDTO::fromArray([
            'name' => '',
            'value' => '',
        ], validate: false);

        expect($dto->isEmpty())->toBeTrue();
    });

    it('isNotEmpty returns true for DTO with non-empty values', function () {
        $dto = MinimalDTO::fromArray([
            'name' => 'test',
            'value' => 'value',
        ], validate: false);

        expect($dto->isNotEmpty())->toBeTrue();
    });

    it('only returns subset of properties', function () {
        $dto = ArticleDTO::fromArray([
            'authorEmail' => 'test@example.com',
            'title' => 'Test Article Title',
            'body' => 'Article body content here for testing',
        ], validate: false);

        $only = $dto->only('title');

        expect($only)->toHaveKey('title');
        expect($only)->not->toHaveKey('authorEmail');
        expect($only)->not->toHaveKey('body');
    });

    it('except returns properties excluding specified keys', function () {
        $dto = ArticleDTO::fromArray([
            'authorEmail' => 'test@example.com',
            'title' => 'Test Article Title',
            'body' => 'Article body content here for testing',
        ], validate: false);

        $except = $dto->except('title');

        expect($except)->not->toHaveKey('title');
        expect($except)->toHaveKey('authorEmail');
    });

    it('only with array of keys returns multiple properties', function () {
        $dto = ArticleDTO::fromArray([
            'authorEmail' => 'test@example.com',
            'title' => 'Test Article Title',
            'body' => 'Article body content here for testing',
        ], validate: false);

        $only = $dto->only(['title', 'body']);

        expect($only)->toHaveKeys(['title', 'body']);
        expect($only)->not->toHaveKey('authorEmail');
    });

    it('except with array of keys excludes multiple properties', function () {
        $dto = ArticleDTO::fromArray([
            'authorEmail' => 'test@example.com',
            'title' => 'Test Article Title',
            'body' => 'Article body content here for testing',
        ], validate: false);

        $except = $dto->except(['title', 'body']);

        expect($except)->not->toHaveKey('title');
        expect($except)->not->toHaveKey('body');
    });

    it('with creates a new instance with overrides', function () {
        $dto = ArticleDTO::fromArray([
            'authorEmail' => 'test@example.com',
            'title' => 'Original Title',
            'body' => 'Article body content here for testing',
        ], validate: false);

        $modified = $dto->with(['title' => 'Modified Title']);

        expect($modified)->toBeInstanceOf(ArticleDTO::class);
        expect($modified)->not->toBe($dto);
    });

    it('with preserves unchanged values', function () {
        $dto = ArticleDTO::fromArray([
            'authorEmail' => 'test@example.com',
            'title' => 'Original Title',
            'body' => 'Article body content here for testing',
            'internalNote' => 'note',
        ], validate: false);

        $modified = $dto->with(['title' => 'Modified Title']);

        $modifiedArray = $modified->allValues();

        expect($modifiedArray['title'])->toBe('Modified Title');
        expect($modifiedArray['authorEmail'])->toBe('test@example.com');
        expect($modifiedArray['internalNote'])->toBe('note');
    });

    it('toJson produces valid JSON', function () {
        $dto = ArticleDTO::fromArray([
            'authorEmail' => 'test@example.com',
            'title' => 'Test',
            'body' => 'Article body content here for testing',
            'internalNote' => 'secret',
        ], validate: false);

        $json = $dto->toJson();
        $decoded = json_decode($json, true);

        expect($json)->toBeJson();
        expect($decoded)->toBeArray();
        expect($decoded['title'])->toBe('Test');
        // hidden property should not be in JSON
        expect($decoded)->not->toHaveKey('internalNote');
    });

    it('fromJson roundtrips correctly', function () {
        $dto = ArticleDTO::fromArray([
            'authorEmail' => 'test@example.com',
            'title' => 'Test',
            'body' => 'Article body content here for testing',
        ], validate: false);

        $json = $dto->toJson();
        $restored = ArticleDTO::fromJson($json, validate: false);

        expect($restored->toArray())->toBe($dto->toArray());
    });

    it('fromJson rejects sequential arrays', function () {
        expect(fn () => MinimalDTO::fromJson('[1,2,3]', validate: false))
            ->toThrow(\ZeroBoiler\DTO\Exceptions\DTOException::class);
    });

    it('fromJson rejects invalid JSON', function () {
        expect(fn () => MinimalDTO::fromJson('{invalid json}', validate: false))
            ->toThrow(\ZeroBoiler\DTO\Exceptions\DTOException::class);
    });

    it('rules returns non-empty array', function () {
        $rules = ArticleDTO::rules();

        expect($rules)->toBeArray()->not->toBeEmpty();
    });

    it('rulesFor returns same rules as rules by default', function () {
        expect(ArticleDTO::rulesFor('create'))
            ->toBe(ArticleDTO::rules());
    });

    it('fromPartialArray uses defaults for missing fields', function () {
        $dto = ArticleDTO::fromPartialArray(['title' => 'Partial'], validate: false);

        expect($dto)->toBeInstanceOf(ArticleDTO::class);
    });

    it('jsonSerialize returns same as toArray', function () {
        $dto = ArticleDTO::fromArray([
            'authorEmail' => 'test@example.com',
            'title' => 'Test',
            'body' => 'Article body content here for testing',
        ], validate: false);

        expect($dto->jsonSerialize())->toBe($dto->toArray());
    });

    it('enum properties are hydrated as enum instances', function () {
        $dto = ArticleDTO::fromArray([
            'authorEmail' => 'test@example.com',
            'title' => 'Test',
            'body' => 'Article body content here for testing',
            'status' => 1,
            'currency' => 'EUR',
        ], validate: false);

        expect($dto->status)->toBeInstanceOf(ArticleStatus::class);
        expect($dto->status)->toBe(ArticleStatus::PUBLISHED);
        expect($dto->currency)->toBeInstanceOf(Currency::class);
        expect($dto->currency)->toBe(Currency::EUR);
    });

    it('enum properties serialize to backed values', function () {
        $dto = ArticleDTO::fromArray([
            'authorEmail' => 'test@example.com',
            'title' => 'Test',
            'body' => 'Article body content here for testing',
            'status' => 2,
            'currency' => 'TRY',
        ], validate: false);

        $array = $dto->toArray();

        expect($array['status'])->toBe(2);
        expect($array['currency'])->toBe('TRY');
    });

    it('cast properties are transformed during hydration', function () {
        $dto = ArticleDTO::fromArray([
            'authorEmail' => 'test@example.com',
            'title' => 'Test',
            'body' => 'Article body content here for testing',
            'viewCount' => '42',
            'rating' => '4.5',
        ], validate: false);

        expect($dto->viewCount)->toBe(42);
        expect($dto->rating)->toBe(4.5);
    });

    it('default values are applied for missing keys', function () {
        $dto = ArticleDTO::fromArray([
            'authorEmail' => 'test@example.com',
            'title' => 'Test',
            'body' => 'Article body content here for testing',
        ], validate: false);

        expect($dto->status)->toBe(ArticleStatus::DRAFT);
        expect($dto->currency)->toBe(Currency::USD);
        expect($dto->viewCount)->toBe(0);
        expect($dto->rating)->toBe(0.0);
        expect($dto->commentsEnabled)->toBeTrue();
    });

    it('nullable properties accept null', function () {
        $dto = ArticleDTO::fromArray([
            'authorEmail' => 'test@example.com',
            'title' => 'Test',
            'body' => 'Article body content here for testing',
            'coverImageUrl' => null,
            'excerpt' => null,
        ], validate: false);

        expect($dto->coverImageUrl)->toBeNull();
        expect($dto->excerpt)->toBeNull();
    });
});

describe('DtoCollection contract', function () {
    it('make creates empty collection', function () {
        $collection = DtoCollection::make();

        expect($collection)->toBeInstanceOf(DtoCollection::class);
        expect($collection->count())->toBe(0);
        expect($collection->isEmpty())->toBeTrue();
    });

    it('make creates collection from DTOs', function () {
        $dto1 = MinimalDTO::fromArray(['name' => 'a', 'value' => '1'], validate: false);
        $dto2 = MinimalDTO::fromArray(['name' => 'b', 'value' => '2'], validate: false);

        $collection = DtoCollection::make([$dto1, $dto2]);

        expect($collection->count())->toBe(2);
        expect($collection->isNotEmpty())->toBeTrue();
    });

    it('push mutates and returns collection', function () {
        $collection = DtoCollection::make();
        $dto = MinimalDTO::fromArray(['name' => 'a', 'value' => '1'], validate: false);

        $result = $collection->push($dto);

        expect($collection->count())->toBe(1);
        expect($result)->toBe($collection);
    });

    it('append returns new collection', function () {
        $dto1 = MinimalDTO::fromArray(['name' => 'a', 'value' => '1'], validate: false);
        $dto2 = MinimalDTO::fromArray(['name' => 'b', 'value' => '2'], validate: false);

        $collection = DtoCollection::make([$dto1]);
        $newCollection = $collection->append($dto2);

        expect($collection->count())->toBe(1);
        expect($newCollection->count())->toBe(2);
    });

    it('filter returns new collection with matching items', function () {
        $dto1 = MinimalDTO::fromArray(['name' => 'keep', 'value' => '1'], validate: false);
        $dto2 = MinimalDTO::fromArray(['name' => 'drop', 'value' => '2'], validate: false);

        $collection = DtoCollection::make([$dto1, $dto2]);
        $filtered = $collection->filter(fn ($dto) => $dto->toArray()['name'] === 'keep');

        expect($filtered->count())->toBe(1);
    });

    it('map returns plain array of results', function () {
        $dto1 = MinimalDTO::fromArray(['name' => 'first', 'value' => '1'], validate: false);
        $dto2 = MinimalDTO::fromArray(['name' => 'second', 'value' => '2'], validate: false);

        $collection = DtoCollection::make([$dto1, $dto2]);
        $names = $collection->map(fn ($dto) => $dto->toArray()['name']);

        expect($names)->toBe(['first', 'second']);
    });

    it('first and last return correct items', function () {
        $dto1 = MinimalDTO::fromArray(['name' => 'first', 'value' => '1'], validate: false);
        $dto2 = MinimalDTO::fromArray(['name' => 'second', 'value' => '2'], validate: false);

        $collection = DtoCollection::make([$dto1, $dto2]);

        expect($collection->first()->toArray()['name'])->toBe('first');
        expect($collection->last()->toArray()['name'])->toBe('second');
    });

    it('first and last return null for empty collection', function () {
        $collection = DtoCollection::make();

        expect($collection->first())->toBeNull();
        expect($collection->last())->toBeNull();
    });

    it('offsetGet returns items by index', function () {
        $dto1 = MinimalDTO::fromArray(['name' => 'first', 'value' => '1'], validate: false);
        $dto2 = MinimalDTO::fromArray(['name' => 'second', 'value' => '2'], validate: false);

        $collection = DtoCollection::make([$dto1, $dto2]);

        expect($collection[0]->toArray()['name'])->toBe('first');
        expect($collection[1]->toArray()['name'])->toBe('second');
        expect($collection[99])->toBeNull();
    });

    it('offsetExists checks indices', function () {
        $dto = MinimalDTO::fromArray(['name' => 'test', 'value' => '1'], validate: false);
        $collection = DtoCollection::make([$dto]);

        expect(isset($collection[0]))->toBeTrue();
        expect(isset($collection[1]))->toBeFalse();
    });

    it('offsetUnset re-indexes the collection', function () {
        $dto1 = MinimalDTO::fromArray(['name' => 'a', 'value' => '1'], validate: false);
        $dto2 = MinimalDTO::fromArray(['name' => 'b', 'value' => '2'], validate: false);
        $dto3 = MinimalDTO::fromArray(['name' => 'c', 'value' => '3'], validate: false);

        $collection = DtoCollection::make([$dto1, $dto2, $dto3]);
        unset($collection[0]);

        expect($collection->count())->toBe(2);
        expect($collection[0]->toArray()['name'])->toBe('b');
        expect($collection[1]->toArray()['name'])->toBe('c');
    });

    it('merge combines two collections', function () {
        $dto1 = MinimalDTO::fromArray(['name' => 'a', 'value' => '1'], validate: false);
        $dto2 = MinimalDTO::fromArray(['name' => 'b', 'value' => '2'], validate: false);
        $dto3 = MinimalDTO::fromArray(['name' => 'c', 'value' => '3'], validate: false);

        $col1 = DtoCollection::make([$dto1]);
        $col2 = DtoCollection::make([$dto2, $dto3]);
        $merged = $col1->merge($col2);

        expect($merged->count())->toBe(3);
    });

    it('toArray serializes all DTOs', function () {
        $dto1 = MinimalDTO::fromArray(['name' => 'a', 'value' => '1'], validate: false);
        $dto2 = MinimalDTO::fromArray(['name' => 'b', 'value' => '2'], validate: false);

        $collection = DtoCollection::make([$dto1, $dto2]);
        $array = $collection->toArray();

        expect($array)->toBeArray();
        expect(count($array))->toBe(2);
        expect($array[0])->toBeArray();
    });

    it('jsonSerialize returns toArray output', function () {
        $dto = MinimalDTO::fromArray(['name' => 'test', 'value' => '1'], validate: false);
        $collection = DtoCollection::make([$dto]);

        expect($collection->jsonSerialize())->toBe($collection->toArray());
    });

    it('is iterable via foreach', function () {
        $dto1 = MinimalDTO::fromArray(['name' => 'a', 'value' => '1'], validate: false);
        $dto2 = MinimalDTO::fromArray(['name' => 'b', 'value' => '2'], validate: false);

        $collection = DtoCollection::make([$dto1, $dto2]);

        $count = 0;
        foreach ($collection as $item) {
            expect($item)->toBeInstanceOf(DataTransferObject::class);
            $count++;
        }

        expect($count)->toBe(2);
    });

    it('rejects non-DTO items in constructor', function () {
        expect(fn () => new DtoCollection(['not-a-dto']))
            ->toThrow(\InvalidArgumentException::class);
    });

    it('rejects non-DTO items in offsetSet', function () {
        $collection = DtoCollection::make();

        expect(fn () => $collection[] = 'not-a-dto')
            ->toThrow(\InvalidArgumentException::class);
    });

    it('pluck extracts property values via reflection', function () {
        $dto1 = MinimalDTO::fromArray(['name' => 'alpha', 'value' => '1'], validate: false);
        $dto2 = MinimalDTO::fromArray(['name' => 'beta', 'value' => '2'], validate: false);

        $collection = DtoCollection::make([$dto1, $dto2]);
        $names = $collection->pluck('name');

        expect($names)->toBe(['alpha', 'beta']);
    });

    it('items() returns raw DTO instances', function () {
        $dto1 = MinimalDTO::fromArray(['name' => 'a', 'value' => '1'], validate: false);
        $dto2 = MinimalDTO::fromArray(['name' => 'b', 'value' => '2'], validate: false);

        $collection = DtoCollection::make([$dto1, $dto2]);
        $items = $collection->items();

        expect($items)->toHaveCount(2);
        expect($items[0])->toBeInstanceOf(MinimalDTO::class);
        expect($items[1])->toBeInstanceOf(MinimalDTO::class);
    });

    it('allValues includes hidden properties in nested DTOs', function () {
        $dto1 = ArticleDTO::fromArray([
            'authorEmail' => 'a@test.com',
            'title' => 'T1',
            'body' => 'Article body content here for testing',
            'internalNote' => 'note1',
        ], validate: false);

        $dto2 = ArticleDTO::fromArray([
            'authorEmail' => 'b@test.com',
            'title' => 'T2',
            'body' => 'Article body content here for testing',
            'internalNote' => 'note2',
        ], validate: false);

        $collection = DtoCollection::make([$dto1, $dto2]);
        $all = $collection->allValues();

        // allValues should include hidden internalNote
        expect($all[0])->toHaveKey('internalNote');
        expect($all[0]['internalNote'])->toBe('note1');
    });

    it('toArray excludes hidden properties in nested DTOs', function () {
        $dto = ArticleDTO::fromArray([
            'authorEmail' => 'test@example.com',
            'title' => 'Test',
            'body' => 'Article body content here for testing',
            'internalNote' => 'hidden-note',
        ], validate: false);

        $collection = DtoCollection::make([$dto]);
        $array = $collection->toArray();

        expect($array[0])->not->toHaveKey('internalNote');
    });
});
