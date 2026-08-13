<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

/**
 * DTO Exception Named Constructors and Edge Case Test.
 *
 * Verifies DTOException factory methods produce correct messages,
 * __toString() formatting, and DtoCollection edge cases
 * (immutability, push vs append, pluckKey null handling, array access).
 *
 * @see \ZeroBoiler\DTO\Exceptions\DTOException
 * @see \ZeroBoiler\DTO\DtoCollection
 */
use ReflectionClass;
use ZeroBoiler\DTO\Attributes\Hidden;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;
use ZeroBoiler\DTO\Tests\Fixtures\ProductDTO;
use ZeroBoiler\DTO\Tests\Fixtures\StrictValidationDTO;

describe('DTOException Named Constructors', function () {
    it('invalidCast() produces correct message with property and type', function () {
        $exception = DTOException::invalidCast('age', 'integer', 'not_a_number');

        expect($exception)->toBeInstanceOf(DTOException::class);
        expect($exception->getMessage())->toBe('Cannot cast property [age] value [string] to [integer].');
    });

    it('invalidCast() handles null value type', function () {
        $exception = DTOException::invalidCast('status', 'string', null);

        expect($exception->getMessage())->toBe('Cannot cast property [status] value [null] to [string].');
    });

    it('invalidCast() handles int value type', function () {
        $exception = DTOException::invalidCast('name', 'string', 42);

        expect($exception->getMessage())->toBe('Cannot cast property [name] value [int] to [string].');
    });

    it('invalidCast() handles array value type', function () {
        $exception = DTOException::invalidCast('tags', 'string', ['a', 'b']);

        expect($exception->getMessage())->toBe('Cannot cast property [tags] value [array] to [string].');
    });

    it('invalidJson() produces correct message with property and error', function () {
        $exception = DTOException::invalidJson('metadata', 'Syntax error');

        expect($exception)->toBeInstanceOf(DTOException::class);
        expect($exception->getMessage())->toBe('Cannot decode JSON for property [metadata]: Syntax error');
    });

    it('invalidJson() handles empty error string', function () {
        $exception = DTOException::invalidJson('data', '');

        expect($exception->getMessage())->toBe('Cannot decode JSON for property [data]: ');
    });

    it('__toString() returns class name colon message', function () {
        $exception = DTOException::invalidCast('field', 'int', 'bad');

        $string = (string) $exception;
        expect($string)->toBe(DTOException::class . ': Cannot cast property [field] value [string] to [int].');
    });

    it('is final class', function () {
        $ref = new ReflectionClass(DTOException::class);
        expect($ref->isFinal())->toBeTrue('DTOException must be final');
    });

    it('extends Exception', function () {
        $ref = new ReflectionClass(DTOException::class);
        expect($ref->isSubclassOf(\Exception::class))->toBeTrue();
    });

    it('invalidJson() factory with root property context', function () {
        $exception = DTOException::invalidJson('(root)', 'Expected a JSON object, got a sequential array');

        expect($exception->getMessage())->toBe(
            'Cannot decode JSON for property [(root)]: Expected a JSON object, got a sequential array'
        );
    });
});

describe('DtoCollection Immutability and Mutation', function () {
    it('append() returns new collection without mutating original', function () {
        $dto = new ProductDTO(name: 'Product A', price: '100');
        $dto2 = new ProductDTO(name: 'Product B', price: '200');

        $collection = new DtoCollection([$dto]);
        $newCollection = $collection->append($dto2);

        expect($collection->count())->toBe(1);
        expect($newCollection->count())->toBe(2);
        expect($newCollection)->not->toBe($collection);
    });

    it('merge() returns new collection without mutating originals', function () {
        $dto1 = new ProductDTO(name: 'A', price: '10');
        $dto2 = new ProductDTO(name: 'B', price: '20');
        $dto3 = new ProductDTO(name: 'C', price: '30');

        $col1 = new DtoCollection([$dto1]);
        $col2 = new DtoCollection([$dto2, $dto3]);
        $merged = $col1->merge($col2);

        expect($col1->count())->toBe(1);
        expect($col2->count())->toBe(2);
        expect($merged->count())->toBe(3);
    });

    it('push() mutates in-place and returns same instance', function () {
        $dto1 = new ProductDTO(name: 'A', price: '10');
        $dto2 = new ProductDTO(name: 'B', price: '20');

        $collection = new DtoCollection([$dto1]);
        $result = $collection->push($dto2);

        expect($collection->count())->toBe(2);
        expect($result)->toBe($collection);
    });

    it('filter() returns new collection with correct items', function () {
        $dto1 = new ProductDTO(name: 'Cheap', price: '5');
        $dto2 = new ProductDTO(name: 'Expensive', price: '500');
        $dto3 = new ProductDTO(name: 'Medium', price: '50');

        $collection = new DtoCollection([$dto1, $dto2, $dto3]);
        $filtered = $collection->filter(
            fn (DataTransferObject $d): bool => (int) $d->toArray()['price'] > 10
        );

        expect($filtered->count())->toBe(2);
        expect($collection->count())->toBe(3);
    });

    it('pluckKey() skips items with null key values', function () {
        $dto1 = new EmptyDTO(foo: 'alpha', bar: '10');
        $dto2 = new EmptyDTO(foo: null, bar: '20');

        $collection = new DtoCollection([$dto1, $dto2]);
        $result = $collection->pluckKey('foo', 'bar');

        expect($result)->toHaveCount(1);
        expect($result)->toHaveKey('alpha');
        expect($result['alpha'])->toBe('10');
    });

    it('toArrayBy() is alias for pluckKey()', function () {
        $dto1 = new ProductDTO(name: 'X', price: '99');
        $collection = new DtoCollection([$dto1]);

        expect($collection->toArrayBy('name'))->toBe($collection->pluckKey('name'));
    });

    it('toDictionary() extracts key-value pairs correctly', function () {
        $dto1 = new ProductDTO(name: 'Alpha', price: '100');
        $dto2 = new ProductDTO(name: 'Beta', price: '200');

        $collection = new DtoCollection([$dto1, $dto2]);
        $dict = $collection->toDictionary('name', 'price');

        expect($dict)->toBe(['Alpha' => '100', 'Beta' => '200']);
    });

    it('toDictionary() skips null key values', function () {
        $dto1 = new EmptyDTO(foo: null, bar: '100');
        $dto2 = new EmptyDTO(foo: 'Valid', bar: '200');

        $collection = new DtoCollection([$dto1, $dto2]);
        $dict = $collection->toDictionary('foo', 'bar');

        expect($dict)->toBe(['Valid' => '200']);
    });

    it('offsetUnset re-indexes the collection', function () {
        $dto1 = new ProductDTO(name: 'A', price: '10');
        $dto2 = new ProductDTO(name: 'B', price: '20');
        $dto3 = new ProductDTO(name: 'C', price: '30');

        $collection = new DtoCollection([$dto1, $dto2, $dto3]);
        unset($collection[0]);

        expect($collection->count())->toBe(2);
        expect($collection[0]->toArray()['name'])->toBe('B');
        expect($collection[1]->toArray()['name'])->toBe('C');
    });

    it('map() returns correct types', function () {
        $dto1 = new ProductDTO(name: 'A', price: '10');
        $dto2 = new ProductDTO(name: 'B', price: '20');

        $collection = new DtoCollection([$dto1, $dto2]);
        $names = $collection->map(fn (DataTransferObject $d, int $index): string => $d->toArray()['name'] . '-' . $index);

        expect($names)->toBe(['A-0', 'B-1']);
    });

    it('isEmpty() and isNotEmpty() work correctly', function () {
        $empty = new DtoCollection([]);
        $nonEmpty = new DtoCollection([new ProductDTO(name: 'X', price: '1')]);

        expect($empty->isEmpty())->toBeTrue();
        expect($empty->isNotEmpty())->toBeFalse();
        expect($nonEmpty->isEmpty())->toBeFalse();
        expect($nonEmpty->isNotEmpty())->toBeTrue();
    });

    it('first() and last() return correct items', function () {
        $dto1 = new ProductDTO(name: 'First', price: '1');
        $dto2 = new ProductDTO(name: 'Last', price: '99');

        $collection = new DtoCollection([$dto1, $dto2]);

        expect($collection->first()->toArray()['name'])->toBe('First');
        expect($collection->last()->toArray()['name'])->toBe('Last');
    });

    it('first() and last() return null for empty collection', function () {
        $collection = new DtoCollection([]);

        expect($collection->first())->toBeNull();
        expect($collection->last())->toBeNull();
    });

    it('jsonSerialize() returns array of arrays', function () {
        $dto = new ProductDTO(name: 'Test', price: '50');
        $collection = new DtoCollection([$dto]);

        $serialized = $collection->jsonSerialize();

        expect($serialized)->toBeArray();
        expect($serialized)->toHaveCount(1);
        expect($serialized[0])->toBe($dto->toArray());
    });

    it('make() creates collection from array', function () {
        $dto = new ProductDTO(name: 'M', price: '42');
        $collection = DtoCollection::make([$dto]);

        expect($collection->count())->toBe(1);
        expect($collection->first()->toArray()['name'])->toBe('M');
    });

    it('make() creates empty collection from empty array', function () {
        $collection = DtoCollection::make([]);

        expect($collection->isEmpty())->toBeTrue();
    });

    it('rejects non-DTO items in constructor', function () {
        expect(fn () => new DtoCollection(['not', 'a', 'dto']))
            ->toThrow(\InvalidArgumentException::class);
    });

    it('items() returns raw DTO instances', function () {
        $dto = new ProductDTO(name: 'Raw', price: '10');
        $collection = new DtoCollection([$dto]);

        $items = $collection->items();

        expect($items)->toHaveCount(1);
        expect($items[0])->toBe($dto);
    });

    it('allValues() includes hidden properties', function () {
        $dto = new ProductDTO(name: 'AV', price: '10');
        $collection = new DtoCollection([$dto]);

        $all = $collection->allValues();

        expect($all)->toBe($collection->toArray());
    });

    it('supports StrictValidationDTO in collection', function () {
        $dto1 = new StrictValidationDTO(name: 'Alice', age: 30);
        $dto2 = new StrictValidationDTO(name: 'Bob', age: 25);

        $collection = new DtoCollection([$dto1, $dto2]);

        expect($collection->count())->toBe(2);
        expect($collection->first()->toArray()['name'])->toBe('Alice');
        expect($collection->last()->toArray()['age'])->toBe(25);
    });

    it('supports heterogeneous DTO types in collection', function () {
        $dto1 = new ProductDTO(name: 'Item', price: '99');
        $dto2 = new EmptyDTO(foo: 'test', bar: null);

        $collection = new DtoCollection([$dto1, $dto2]);

        expect($collection->count())->toBe(2);
        expect($collection->first()->toArray()['name'])->toBe('Item');
        expect($collection->last()->toArray()['foo'])->toBe('test');
    });

    it('jsonSerialize() output matches toArray()', function () {
        $dto = new ProductDTO(name: 'Ser', price: '10');
        $collection = new DtoCollection([$dto]);

        expect($collection->jsonSerialize())->toBe($collection->toArray());
    });
});

describe('DTO Hidden Attribute Contract', function () {
    it('Hidden attribute is final with TARGET_PROPERTY', function () {
        $ref = new ReflectionClass(Hidden::class);
        expect($ref->isFinal())->toBeTrue();

        $attrs = $ref->getAttributes(\Attribute::class);
        expect($attrs)->toHaveCount(1);

        $attr = $attrs[0]->newInstance();
        expect($attr->getFlags())->toBe(\Attribute::TARGET_PROPERTY);
    });

    it('Hidden attribute has no constructor parameters', function () {
        $ref = new ReflectionClass(Hidden::class);
        $constructor = $ref->getConstructor();

        expect($constructor)->not->toBeNull();
        expect($constructor->getNumberOfParameters())->toBe(0);
    });
});
