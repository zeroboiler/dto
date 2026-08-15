<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\Attributes\Collection;
use ZeroBoiler\DTO\Attributes\DefaultValue;
use ZeroBoiler\DTO\Attributes\Hidden;
use ZeroBoiler\DTO\Attributes\MapFrom;
use ZeroBoiler\DTO\Attributes\NestedArray;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\Casts\DTOCast;
use ZeroBoiler\DTO\Contracts\FromRequestDTO;
use ZeroBoiler\DTO\Contracts\ValidatableDTO;
use ZeroBoiler\DTO\Contracts\ValidationAttribute;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DTOManager;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Facades\DTO;
use ZeroBoiler\DTO\Support\DtoMetadataResolver;
use ZeroBoiler\DTO\Support\OpenApiSchemaGenerator;
use ZeroBoiler\DTO\Tests\Fixtures\AddressDTO;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\OrderDTO;

describe('V19 — Production Type Safety And Contract Hardening', function () {
    // ─── DTO: Infrastructure classes are final ─────────────────────────

    it('DTOManager is final and readonly', function () {
        $ref = new \ReflectionClass(DTOManager::class);
        expect($ref->isFinal())->toBeTrue();
        expect($ref->isReadOnly())->toBeTrue();
    });

    it('DtoCollection is final', function () {
        $ref = new \ReflectionClass(DtoCollection::class);
        expect($ref->isFinal())->toBeTrue();
    });

    it('DTOCast is final', function () {
        $ref = new \ReflectionClass(DTOCast::class);
        expect($ref->isFinal())->toBeTrue();
    });

    it('DTOException is final', function () {
        $ref = new \ReflectionClass(DTOException::class);
        expect($ref->isFinal())->toBeTrue();
    });

    it('DtoMetadataResolver is final', function () {
        $ref = new \ReflectionClass(DtoMetadataResolver::class);
        expect($ref->isFinal())->toBeTrue();
    });

    it('OpenApiSchemaGenerator is final', function () {
        $ref = new \ReflectionClass(OpenApiSchemaGenerator::class);
        expect($ref->isFinal())->toBeTrue();
    });

    it('DataTransferObject is abstract', function () {
        $ref = new \ReflectionClass(DataTransferObject::class);
        expect($ref->isAbstract())->toBeTrue();
    });

    // ─── DTO: Interfaces are correctly defined ──────────────────────────

    it('FromRequestDTO interface has fromRequest method', function () {
        $ref = new \ReflectionMethod(FromRequestDTO::class, 'fromRequest');
        expect($ref->isPublic())->toBeTrue();
        expect($ref->isStatic())->toBeTrue();
    });

    it('ValidatableDTO interface has rules and rulesFor methods', function () {
        expect(new \ReflectionMethod(ValidatableDTO::class, 'rules'))->not->toBeNull();
        expect(new \ReflectionMethod(ValidatableDTO::class, 'rulesFor'))->not->toBeNull();
    });

    it('ValidationAttribute interface has ruleKey method', function () {
        $ref = new \ReflectionMethod(ValidationAttribute::class, 'ruleKey');
        expect($ref->getReturnType()->getName())->toBe('string');
    });

    // ─── DTO: Attribute classes are final with correct types ───────────

    it('Required attribute is final', function () {
        expect(new \ReflectionClass(Required::class))->isFinal()->toBeTrue();
    });

    it('Hidden attribute is final with no constructor', function () {
        $ref = new \ReflectionClass(Hidden::class);
        expect($ref->isFinal())->toBeTrue();
        expect($ref->getConstructor())->toBeNull();
    });

    it('MapFrom attribute is final with readonly string key', function () {
        $ref = new \ReflectionClass(MapFrom::class);
        expect($ref->isFinal())->toBeTrue();
        $prop = new \ReflectionProperty(MapFrom::class, 'key');
        expect($prop->isReadOnly())->toBeTrue();
        expect($prop->getType()->getName())->toBe('string');
    });

    it('DefaultValue attribute has mixed readonly value', function () {
        $ref = new \ReflectionClass(DefaultValue::class);
        expect($ref->isFinal())->toBeTrue();
        $prop = new \ReflectionProperty(DefaultValue::class, 'value');
        expect($prop->isReadOnly())->toBeTrue();
    });

    it('NestedArray attribute implements ValidationAttribute', function () {
        expect(new \ReflectionClass(NestedArray::class))->isFinal()->toBeTrue();
        expect(NestedArray::class)->toImplement(ValidationAttribute::class);
    });

    it('Collection attribute implements ValidationAttribute', function () {
        expect(new \ReflectionClass(Collection::class))->isFinal()->toBeTrue();
        expect(Collection::class)->toImplement(ValidationAttribute::class);
    });

    // ─── DTO: DTOException factory methods ─────────────────────────────

    it('DTOException::invalidCast includes property and type', function () {
        $ex = DTOException::invalidCast('age', 'integer', 'hello');
        expect($ex->getMessage())->toContain('age');
        expect($ex->getMessage())->toContain('integer');
    });

    it('DTOException::invalidJson includes property and error', function () {
        $ex = DTOException::invalidJson('payload', 'syntax error');
        expect($ex->getMessage())->toContain('payload');
        expect($ex->getMessage())->toContain('syntax error');
    });

    it('DTOException::__toString includes class name', function () {
        $ex = DTOException::invalidCast('x', 'y', 'z');
        $str = (string) $ex;
        expect($str)->toContain('DTOException');
    });

    // ─── DTO: DtoCollection immutability contract ──────────────────────

    it('DtoCollection __clone always throws', function () {
        $col = DtoCollection::make([]);
        expect(fn () => clone $col)->toThrow(\RuntimeException::class);
    });

    it('DtoCollection append returns new instance', function () {
        $address = ['street' => 'Main', 'city' => 'Istanbul', 'zipCode' => '34000'];
        $dto = OrderDTO::fromArray(['orderNumber' => 'ORD-1', 'shippingAddress' => $address], validate: false);
        $col = DtoCollection::make([$dto]);
        $newCol = $col->append($dto);

        expect($newCol)->not->toBe($col);
        expect($newCol->count())->toBe(2);
        expect($col->count())->toBe(1);
    });

    it('DtoCollection merge creates new combined collection', function () {
        $address = ['street' => 'Main', 'city' => 'Istanbul', 'zipCode' => '34000'];
        $dto1 = OrderDTO::fromArray(['orderNumber' => 'ORD-1', 'shippingAddress' => $address], validate: false);
        $dto2 = OrderDTO::fromArray(['orderNumber' => 'ORD-2', 'shippingAddress' => $address], validate: false);

        $col1 = DtoCollection::make([$dto1]);
        $col2 = DtoCollection::make([$dto2]);
        $merged = $col1->merge($col2);

        expect($merged->count())->toBe(2);
        expect($col1->count())->toBe(1);
        expect($col2->count())->toBe(1);
    });

    // ─── DTO: DtoCollection type guard ─────────────────────────────────

    it('DtoCollection rejects non-DTO items', function () {
        expect(fn () => new DtoCollection(['not', 'dtos']))->toThrow(\InvalidArgumentException::class);
    });

    it('DtoCollection offsetSet rejects non-DTO values', function () {
        $col = DtoCollection::make([]);
        expect(fn () => $col['key'] = 'invalid')->toThrow(\InvalidArgumentException::class);
    });

    // ─── DTO: DtoCollection offsetUnset re-indexes ─────────────────────

    it('DtoCollection offsetUnset re-indexes after removal', function () {
        $address = ['street' => 'Main', 'city' => 'Istanbul', 'zipCode' => '34000'];
        $dto1 = OrderDTO::fromArray(['orderNumber' => 'ORD-1', 'shippingAddress' => $address], validate: false);
        $dto2 = OrderDTO::fromArray(['orderNumber' => 'ORD-2', 'shippingAddress' => $address], validate: false);
        $dto3 = OrderDTO::fromArray(['orderNumber' => 'ORD-3', 'shippingAddress' => $address], validate: false);

        $col = DtoCollection::make([$dto1, $dto2, $dto3]);
        unset($col[1]);

        expect($col->count())->toBe(2);
        expect($col[0])->toBe($dto1);
        expect($col[1])->toBe($dto3);
    });

    // ─── DTO: DataTransferObject implements contracts ─────────────────

    it('DataTransferObject implements FromRequestDTO', function () {
        expect(DataTransferObject::class)->toImplement(FromRequestDTO::class);
    });

    it('DataTransferObject implements ValidatableDTO', function () {
        expect(DataTransferObject::class)->toImplement(ValidatableDTO::class);
    });

    it('DataTransferObject implements Arrayable', function () {
        expect(DataTransferObject::class)->toImplement(\Illuminate\Contracts\Support\Arrayable::class);
    });

    it('DataTransferObject implements JsonSerializable', function () {
        expect(DataTransferObject::class)->toImplement(\JsonSerializable::class);
    });

    // ─── DTO: DTOCast contract ─────────────────────────────────────────

    it('DTOCast get returns null for null value', function () {
        $cast = new DTOCast(CreateUserDTO::class);
        $result = $cast->get(new \stdClass(), 'data', null, []);
        expect($result)->toBeNull();
    });

    it('DTOCast set returns null for null value', function () {
        $cast = new DTOCast(CreateUserDTO::class);
        $result = $cast->set(new \stdClass(), 'data', null, []);
        expect($result)->toBeNull();
    });

    it('DTOCast set rejects unexpected types', function () {
        $cast = new DTOCast(CreateUserDTO::class);
        expect(fn () => $cast->set(new \stdClass(), 'data', 12345, []))
            ->toThrow(\InvalidArgumentException::class);
    });

    it('DTOCast set serializes DTO to JSON string', function () {
        $cast = new DTOCast(CreateUserDTO::class);
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
            'password' => 'secret123',
        ], validate: false);

        $result = $cast->set(new \stdClass(), 'data', $dto, []);
        expect($result)->toBeJson();
    });

    it('DTOCast serialize returns toArray for DTO instance', function () {
        $cast = new DTOCast(CreateUserDTO::class);
        $dto = CreateUserDTO::fromArray([
            'email' => 'a@b.com',
            'name' => 'Alice',
            'password' => 'secret123',
        ], validate: false);

        $result = $cast->serialize(new \stdClass(), 'data', $dto, []);
        expect($result)->toBeArray();
    });

    it('DTOCast serialize returns null for null value', function () {
        $cast = new DTOCast(CreateUserDTO::class);
        $result = $cast->serialize(new \stdClass(), 'data', null, []);
        expect($result)->toBeNull();
    });

    // ─── DTO: Metadata cache contract ──────────────────────────────────

    it('DataTransferObject::setMetadataCacheTtl sets TTL', function () {
        $originalTtl = 0.0;
        CreateUserDTO::setMetadataCacheTtl(1.0);

        CreateUserDTO::flushMetadataCache();
        $rules = CreateUserDTO::rules();
        expect($rules)->toBeArray();
        expect($rules)->not->toBeEmpty();

        CreateUserDTO::setMetadataCacheTtl($originalTtl);
    });

    it('DataTransferObject::flushMetadataCache clears all', function () {
        CreateUserDTO::flushMetadataCache(null);
        $rules = CreateUserDTO::rules(); // triggers re-resolution
        expect($rules)->toBeArray();
    });

    // ─── DTO: DTO base class has strict types ──────────────────────────

    it('DataTransferObject has declare(strict_types=1)', function () {
        $content = file_get_contents((new \ReflectionClass(DataTransferObject::class))->getFileName());
        expect($content)->toContain('declare(strict_types=1)');
    });

    it('DtoCollection has declare(strict_types=1)', function () {
        $content = file_get_contents((new \ReflectionClass(DtoCollection::class))->getFileName());
        expect($content)->toContain('declare(strict_types=1)');
    });

    it('DtoMetadataResolver has declare(strict_types=1)', function () {
        $content = file_get_contents((new \ReflectionClass(DtoMetadataResolver::class))->getFileName());
        expect($content)->toContain('declare(strict_types=1)');
    });

    it('OpenApiSchemaGenerator has declare(strict_types=1)', function () {
        $content = file_get_contents((new \ReflectionClass(OpenApiSchemaGenerator::class))->getFileName());
        expect($content)->toContain('declare(strict_types=1)');
    });

    it('DTOException has declare(strict_types=1)', function () {
        $content = file_get_contents((new \ReflectionClass(DTOException::class))->getFileName());
        expect($content)->toContain('declare(strict_types=1)');
    });

    // ─── DTO: #[\Override] on key methods ───────────────────────────────

    it('DTOCast has #[Override] on get, set, serialize', function () {
        $ref = new \ReflectionClass(DTOCast::class);

        $get = $ref->getMethod('get');
        expect($get->getAttributes(\Override::class))->not->toBeEmpty();

        $set = $ref->getMethod('set');
        expect($set->getAttributes(\Override::class))->not->toBeEmpty();

        $serialize = $ref->getMethod('serialize');
        expect($serialize->getAttributes(\Override::class))->not->toBeEmpty();
    });

    it('DTOException has #[Override] on __toString', function () {
        $ref = new \ReflectionClass(DTOException::class);
        $method = $ref->getMethod('__toString');
        expect($method->getAttributes(\Override::class))->not->toBeEmpty();
    });

    it('DataTransferObject has #[Override] on interface methods', function () {
        $ref = new \ReflectionClass(DataTransferObject::class);

        $fromRequest = $ref->getMethod('fromRequest');
        expect($fromRequest->getAttributes(\Override::class))->not->toBeEmpty();

        $rules = $ref->getMethod('rules');
        expect($rules->getAttributes(\Override::class))->not->toBeEmpty();

        $rulesFor = $ref->getMethod('rulesFor');
        expect($rulesFor->getAttributes(\Override::class))->not->toBeEmpty();

        $toArray = $ref->getMethod('toArray');
        expect($toArray->getAttributes(\Override::class))->not->toBeEmpty();

        $jsonSerialize = $ref->getMethod('jsonSerialize');
        expect($jsonSerialize->getAttributes(\Override::class))->not->toBeEmpty();
    });

    it('DtoCollection has #[Override] on interface methods', function () {
        $ref = new \ReflectionClass(DtoCollection::class);

        foreach (['count', 'getIterator', 'offsetExists', 'offsetGet', 'offsetSet', 'offsetUnset', 'jsonSerialize'] as $method) {
            $m = $ref->getMethod($method);
            expect($m->getAttributes(\Override::class))->not->toBeEmpty("DtoCollection::{$method}() should have #[Override]");
        }
    });
});
