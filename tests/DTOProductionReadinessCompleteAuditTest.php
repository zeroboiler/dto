<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\Attributes\Between;
use ZeroBoiler\DTO\Attributes\Cast;
use ZeroBoiler\DTO\Attributes\DefaultValue;
use ZeroBoiler\DTO\Attributes\Email;
use ZeroBoiler\DTO\Attributes\Hidden;
use ZeroBoiler\DTO\Attributes\In;
use ZeroBoiler\DTO\Attributes\MapFrom;
use ZeroBoiler\DTO\Attributes\Max;
use ZeroBoiler\DTO\Attributes\Min;
use ZeroBoiler\DTO\Attributes\Pattern;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\Attributes\StartsWith;
use ZeroBoiler\DTO\Attributes\Uuid;
use ZeroBoiler\DTO\Contracts\FromRequestDTO;
use ZeroBoiler\DTO\Contracts\ValidationAttribute;
use ZeroBoiler\DTO\Contracts\ValidatableDTO;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Support\DtoMetadataResolver;
use ZeroBoiler\DTO\Support\OpenApiSchemaGenerator;
use ZeroBoiler\DTO\Tests\Fixtures\AddressDTO;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;
use ZeroBoiler\DTO\Tests\Fixtures\MinimalDTO;

describe('DTO production readiness complete audit', function () {
    describe('strict types and class structure', function () {
        it('all source files have declare(strict_types=1)', function () {
            $srcDir = __DIR__.'/../src';
            $files = glob($srcDir.'/**/*.php') ?: [];
            expect($files)->not->toBeEmpty();

            foreach ($files as $file) {
                $content = file_get_contents($file);
                expect($content)->toContain('declare(strict_types=1)');
            }
        });

        it('all attribute classes are final', function () {
            $attrDir = __DIR__.'/../src/Attributes';
            $files = glob($attrDir.'/*.php') ?: [];

            foreach ($files as $file) {
                $className = 'ZeroBoiler\\DTO\\Attributes\\'.basename($file, '.php');
                if (! class_exists($className)) {
                    continue;
                }
                $ref = new ReflectionClass($className);
                expect($ref->isFinal())->toBeTrue("{$className} must be final");
            }
        });

        it('all service classes are final', function () {
            $classes = [
                DataTransferObject::class,
                DtoCollection::class,
                \ZeroBoiler\DTO\DTOManager::class,
                \ZeroBoiler\DTO\DTOSServiceProvider::class,
                \ZeroBoiler\DTO\Support\DtoMetadataResolver::class,
                \ZeroBoiler\DTO\Support\OpenApiSchemaGenerator::class,
                \ZeroBoiler\DTO\Casts\DTOCast::class,
                \ZeroBoiler\DTO\Exceptions\DTOException::class,
            ];

            foreach ($classes as $class) {
                $ref = new ReflectionClass($class);
                expect($ref->isFinal())->toBeTrue("{$class} must be final");
            }
        });

        it('DataTransferObject is abstract', function () {
            expect((new ReflectionClass(DataTransferObject::class))->isAbstract())->toBeTrue();
        });
    });

    describe('contract implementations', function () {
        it('DataTransferObject implements FromRequestDTO', function () {
            expect(DataTransferObject::class)->toImplement(FromRequestDTO::class);
        });

        it('DataTransferObject implements ValidatableDTO', function () {
            expect(DataTransferObject::class)->toImplement(ValidatableDTO::class);
        });

        it('DataTransferObject implements Arrayable and JsonSerializable', function () {
            $ref = new ReflectionClass(DataTransferObject::class);
            expect($ref->implementsInterface(\Illuminate\Contracts\Support\Arrayable::class))->toBeTrue();
            expect($ref->implementsInterface(\JsonSerializable::class))->toBeTrue();
        });

        it('DtoCollection implements ArrayAccess, Countable, IteratorAggregate, JsonSerializable', function () {
            $ref = new ReflectionClass(DtoCollection::class);
            expect($ref->implementsInterface(\ArrayAccess::class))->toBeTrue();
            expect($ref->implementsInterface(\Countable::class))->toBeTrue();
            expect($ref->implementsInterface(\IteratorAggregate::class))->toBeTrue();
            expect($ref->implementsInterface(\JsonSerializable::class))->toBeTrue();
        });
    });

    describe('attribute readonly promoted properties', function () {
        it('Required attribute has readonly nullable string message', function () {
            $attr = new Required;
            $refMsg = new ReflectionProperty($attr, 'message');
            expect($refMsg->isReadOnly())->toBeTrue();
            expect($attr->message)->toBeNull();

            $attr2 = new Required(message: 'Field is required');
            expect($attr2->message)->toBe('Field is required');
        });

        it('Email attribute has readonly nullable string message', function () {
            $attr = new Email(message: 'Invalid email');
            expect($attr->message)->toBe('Invalid email');
        });

        it('Min attribute has readonly int value', function () {
            $attr = new Min(8);
            $ref = new ReflectionProperty($attr, 'value');
            expect($ref->isReadOnly())->toBeTrue();
            expect($attr->value)->toBe(8);
        });

        it('Max attribute has readonly int value', function () {
            $attr = new Max(255);
            expect($attr->value)->toBe(255);
        });

        it('Between attribute accepts int|float for min and max', function () {
            $attr = new Between(0.5, 99.9);
            expect($attr->min)->toBe(0.5);
            expect($attr->max)->toBe(99.9);

            $attrInt = new Between(1, 100);
            expect($attrInt->min)->toBe(1);
            expect($attrInt->max)->toBe(100);
        });

        it('In attribute has readonly array values', function () {
            $attr = new In(['draft', 'published']);
            $ref = new ReflectionProperty($attr, 'values');
            expect($ref->isReadOnly())->toBeTrue();
            expect($attr->values)->toBe(['draft', 'published']);
        });

        it('Pattern attribute has readonly string regex', function () {
            $attr = new Pattern('/^[A-Z]{3}-\\d{4}$/');
            expect($attr->regex)->toBe('/^[A-Z]{3}-\\d{4}$/');
        });

        it('MapFrom attribute has readonly string key', function () {
            $attr = new MapFrom('user_name');
            expect($attr->key)->toBe('user_name');
        });

        it('Hidden attribute is instantiable', function () {
            $attr = new Hidden;
            $ref = new ReflectionClass($attr);
            expect($ref->isFinal())->toBeTrue();
        });

        it('Cast attribute has readonly string type', function () {
            $attr = new Cast('integer');
            expect($attr->type)->toBe('integer');
        });

        it('DefaultValue attribute has readonly mixed value', function () {
            $attr = new DefaultValue('active');
            expect($attr->value)->toBe('active');
        });

        it('StartsWith attribute accepts string or array', function () {
            $single = new StartsWith('https://');
            expect($single->prefix)->toBe('https://');

            $multi = new StartsWith(['+90', '+1']);
            expect($multi->prefix)->toBe(['+90', '+1']);
        });
    });

    describe('ValidationAttribute contract compliance', function () {
        it('all validation attributes implement ValidationAttribute', function () {
            $validatable = [
                Required::class, Email::class, Max::class, Min::class,
                Between::class, In::class, Pattern::class, StartsWith::class,
                \ZeroBoiler\DTO\Attributes\EndsWith::class,
                \ZeroBoiler\DTO\Attributes\Url::class, \ZeroBoiler\DTO\Attributes\Uuid::class,
                \ZeroBoiler\DTO\Attributes\Integer::class, \ZeroBoiler\DTO\Attributes\Numeric::class,
                \ZeroBoiler\DTO\Attributes\Boolean::class, \ZeroBoiler\DTO\Attributes\Date::class,
                \ZeroBoiler\DTO\Attributes\Json::class,
                \ZeroBoiler\DTO\Attributes\Accepted::class, \ZeroBoiler\DTO\Attributes\Declined::class,
                \ZeroBoiler\DTO\Attributes\Confirmed::class, \ZeroBoiler\DTO\Attributes\Distinct::class,
                \ZeroBoiler\DTO\Attributes\Prohibited::class, \ZeroBoiler\DTO\Attributes\Present::class,
                \ZeroBoiler\DTO\Attributes\Sometimes::class, \ZeroBoiler\DTO\Attributes\Nullable::class,
                \ZeroBoiler\DTO\Attributes\Same::class, \ZeroBoiler\DTO\Attributes\Different::class,
                \ZeroBoiler\DTO\Attributes\RequiredIf::class, \ZeroBoiler\DTO\Attributes\RequiredUnless::class,
                \ZeroBoiler\DTO\Attributes\RequiredWith::class, \ZeroBoiler\DTO\Attributes\RequiredWithAll::class,
                \ZeroBoiler\DTO\Attributes\RequiredWithout::class, \ZeroBoiler\DTO\Attributes\RequiredWithoutAll::class,
                \ZeroBoiler\DTO\Attributes\ArrayRule::class, \ZeroBoiler\DTO\Attributes\Size::class,
                \ZeroBoiler\DTO\Attributes\Enum::class,
            ];

            foreach ($validatable as $class) {
                expect($class)->toImplement(ValidationAttribute::class);
            }
        });

        it('each validation attribute has a ruleKey() method returning non-empty string', function () {
            $attributes = [
                new Required, new Email, new Max(10), new Min(1),
                new Between(1, 10), new In(['a']), new Pattern('/x/'),
                new StartsWith('x'), new \ZeroBoiler\DTO\Attributes\EndsWith('x'),
                new \ZeroBoiler\DTO\Attributes\Url, new \ZeroBoiler\DTO\Attributes\Uuid,
                new \ZeroBoiler\DTO\Attributes\Integer, new \ZeroBoiler\DTO\Attributes\Numeric,
                new \ZeroBoiler\DTO\Attributes\Boolean, new \ZeroBoiler\DTO\Attributes\Date,
                new \ZeroBoiler\DTO\Attributes\Json,
            ];

            foreach ($attributes as $attr) {
                expect($attr->ruleKey())->toBeString()->not->toBeEmpty();
            }
        });
    });

    describe('DTO hydration and serialization', function () {
        it('creates DTO from array without validation', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test User',
            ], validate: false);

            expect($dto)->toBeInstanceOf(CreateUserDTO::class);
            expect($dto->email)->toBe('test@example.com');
            expect($dto->name)->toBe('Test User');
            expect($dto->status)->toBe('active'); // default value
            expect($dto->tags)->toBe([]);
            expect($dto->phone)->toBeNull();
            expect($dto->password)->toBeNull();
        });

        it('applies MapFrom to alias source keys', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test',
                'phone_number' => '+905551234567',
            ], validate: false);

            expect($dto->phone)->toBe('+905551234567');
        });

        it('applies Cast to transform values', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test',
                'tags' => '["a","b"]', // Cast('array') decodes JSON
            ], validate: false);

            expect($dto->tags)->toBe(['a', 'b']);
        });

        it('serializes to array excluding hidden fields', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
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
                'email' => 'test@example.com',
                'name' => 'Test',
                'password' => 'secret123',
            ], validate: false);

            $all = $dto->allValues();
            expect($all)->toHaveKey('password');
            expect($all['password'])->toBe('secret123');
        });

        it('serializes to JSON string', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test',
            ], validate: false);

            $json = $dto->toJson();
            expect($json)->toBeJson();
            $decoded = json_decode($json, true);
            expect($decoded['email'])->toBe('test@example.com');
        });

        it('fromJson parses valid JSON object', function () {
            $dto = MinimalDTO::fromJson('{"name":"hello","value":"world"}', validate: false);

            expect($dto)->toBeInstanceOf(MinimalDTO::class);
            expect($dto->name)->toBe('hello');
            expect($dto->value)->toBe('world');
        });

        it('fromJson throws DTOException for invalid JSON', function () {
            expect(fn () => MinimalDTO::fromJson('{invalid}'))
                ->toThrow(DTOException::class);
        });

        it('fromJson throws DTOException for sequential arrays', function () {
            expect(fn () => MinimalDTO::fromJson('["hello","world"]'))
                ->toThrow(DTOException::class);
        });
    });

    describe('immutable update with with()', function () {
        it('creates new instance with overrides', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test',
            ], validate: false);

            $updated = $dto->with(['status' => 'inactive']);
            expect($dto->status)->toBe('active'); // original unchanged
            expect($updated->status)->toBe('inactive');
        });

        it('equals checks serialized equality', function () {
            $dto1 = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test',
            ], validate: false);

            $dto2 = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test',
            ], validate: false);

            expect($dto1->equals($dto2))->toBeTrue();
        });

        it('equals returns false for different values', function () {
            $dto1 = CreateUserDTO::fromArray([
                'email' => 'a@example.com',
                'name' => 'Test',
            ], validate: false);

            $dto2 = CreateUserDTO::fromArray([
                'email' => 'b@example.com',
                'name' => 'Test',
            ], validate: false);

            expect($dto1->equals($dto2))->toBeFalse();
        });
    });

    describe('state checks', function () {
        it('isEmpty returns true for all-default EmptyDTO', function () {
            $dto = EmptyDTO::fromArray([], validate: false);
            expect($dto->isEmpty())->toBeTrue();
            expect($dto->isNotEmpty())->toBeFalse();
        });

        it('isEmpty returns false when a property has a value', function () {
            $dto = EmptyDTO::fromArray(['foo' => 'bar'], validate: false);
            expect($dto->isEmpty())->toBeFalse();
            expect($dto->isNotEmpty())->toBeTrue();
        });
    });

    describe('selective output', function () {
        it('only returns specified fields', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test',
            ], validate: false);

            $result = $dto->only('email');
            expect($result)->toHaveCount(1);
            expect($result)->toHaveKey('email');
        });

        it('only accepts multiple keys', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test',
            ], validate: false);

            $result = $dto->only('email', 'name');
            expect($result)->toHaveCount(2);
        });

        it('except excludes specified fields', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test',
                'status' => 'active',
            ], validate: false);

            $result = $dto->except('status');
            expect($result)->not->toHaveKey('status');
        });
    });

    describe('validation rules extraction', function () {
        it('rules() returns array of rule arrays', function () {
            $rules = CreateUserDTO::rules();
            expect($rules)->toBeArray();
            expect($rules)->toHaveKey('email');
            expect($rules)->toHaveKey('name');

            expect($rules['email'])->toContain('required');
            expect($rules['email'])->toContain('email');
            expect($rules['name'])->toContain('min:2');
            expect($rules['name'])->toContain('max:50');
        });

        it('rulesFor() returns same as rules() by default', function () {
            expect(CreateUserDTO::rulesFor('create'))->toBe(CreateUserDTO::rules());
            expect(CreateUserDTO::rulesFor('update'))->toBe(CreateUserDTO::rules());
        });
    });

    describe('nested DTO hydration', function () {
        it('hydrates nested DTO from array', function () {
            $dto = \ZeroBoiler\DTO\Tests\Fixtures\OrderDTO::fromArray([
                'orderNumber' => 'ORD-001',
                'shippingAddress' => ['street' => '123 Main St', 'city' => 'Istanbul'],
            ], validate: false);

            expect($dto->shippingAddress)->toBeInstanceOf(AddressDTO::class);
            expect($dto->shippingAddress->street)->toBe('123 Main St');
        });

        it('serializes nested DTO recursively', function () {
            $dto = \ZeroBoiler\DTO\Tests\Fixtures\OrderDTO::fromArray([
                'orderNumber' => 'ORD-001',
                'shippingAddress' => ['street' => '123 Main St', 'city' => 'Istanbul'],
            ], validate: false);

            $arr = $dto->toArray();
            expect($arr['shippingAddress'])->toBeArray();
            expect($arr['shippingAddress']['street'])->toBe('123 Main St');
        });
    });

    describe('DtoCollection operations', function () {
        it('creates collection from DTO instances', function () {
            $dto1 = EmptyDTO::fromArray(['foo' => 'a'], validate: false);
            $dto2 = EmptyDTO::fromArray(['foo' => 'b'], validate: false);

            $collection = new DtoCollection([$dto1, $dto2]);
            expect($collection->count())->toBe(2);
            expect($collection->isEmpty())->toBeFalse();
        });

        it('make factory creates collection', function () {
            $collection = DtoCollection::make();
            expect($collection->count())->toBe(0);
            expect($collection->isEmpty())->toBeTrue();
        });

        it('push appends DTO fluently', function () {
            $dto = EmptyDTO::fromArray(['foo' => 'a'], validate: false);
            $collection = new DtoCollection;
            $result = $collection->push($dto);

            expect($result->count())->toBe(1);
            expect($result)->toBe($collection); // fluent
        });

        it('first and last return correct items', function () {
            $d1 = EmptyDTO::fromArray(['foo' => '1'], validate: false);
            $d2 = EmptyDTO::fromArray(['foo' => '2'], validate: false);
            $d3 = EmptyDTO::fromArray(['foo' => '3'], validate: false);

            $collection = new DtoCollection([$d1, $d2, $d3]);
            expect($collection->first())->toBe($d1);
            expect($collection->last())->toBe($d3);
        });

        it('map returns plain array of results', function () {
            $d1 = EmptyDTO::fromArray(['foo' => 'a'], validate: false);
            $d2 = EmptyDTO::fromArray(['foo' => 'b'], validate: false);

            $collection = new DtoCollection([$d1, $d2]);
            $result = $collection->map(fn (DataTransferObject $dto) => $dto->foo);

            expect($result)->toBe(['a', 'b']);
        });

        it('filter returns new collection', function () {
            $d1 = EmptyDTO::fromArray(['foo' => 'a'], validate: false);
            $d2 = EmptyDTO::fromArray(['foo' => ''], validate: false);

            $collection = new DtoCollection([$d1, $d2]);
            $filtered = $collection->filter(fn (DataTransferObject $dto) => $dto->foo !== '');

            expect($filtered->count())->toBe(1);
        });

        it('rejects non-DTO instances in constructor', function () {
            expect(fn () => new DtoCollection(['not_a_dto']))
                ->toThrow(\InvalidArgumentException::class);
        });

        it('jsonSerialize returns array of arrays', function () {
            $dto = EmptyDTO::fromArray(['foo' => 'a'], validate: false);
            $collection = new DtoCollection([$dto]);

            $result = $collection->jsonSerialize();
            expect($result)->toBeArray();
            expect($result[0])->toBeArray();
        });
    });

    describe('metadata cache management', function () {
        it('flushMetadataCache clears all metadata', function () {
            // Resolve metadata to populate cache
            $rules1 = CreateUserDTO::rules();
            expect($rules1)->not->toBeEmpty();

            DataTransferObject::flushMetadataCache();

            // Re-resolve should work fine
            $rules2 = CreateUserDTO::rules();
            expect($rules2)->toBe($rules1);
        });

        it('flushMetadataCache accepts class-specific clear', function () {
            DataTransferObject::flushMetadataCache(CreateUserDTO::class);
            // Should not throw
            expect(CreateUserDTO::rules())->not->toBeEmpty();
        });
    });

    describe('DtoMetadataResolver', function () {
        it('returns properties, rules, and messages', function () {
            DataTransferObject::flushMetadataCache();
            $meta = DtoMetadataResolver::resolve(CreateUserDTO::class);

            expect($meta)->toHaveKeys(['properties', 'rules', 'messages']);
            expect($meta['properties'])->not->toBeEmpty();
            expect($meta['rules'])->not->toBeEmpty();
        });

        it('properties contain expected metadata keys', function () {
            DataTransferObject::flushMetadataCache();
            $meta = DtoMetadataResolver::resolve(CreateUserDTO::class);

            foreach ($meta['properties'] as $name => $prop) {
                expect($prop)->toHaveKeys([
                    'map_from', 'default', 'has_default', 'cast',
                    'hidden', 'nullable', 'value_object_class',
                    'dto_class', 'enum_class', 'nested_array_class', 'collection_class',
                ]);
            }
        });

        it('detects hidden property from attribute', function () {
            DataTransferObject::flushMetadataCache();
            $meta = DtoMetadataResolver::resolve(CreateUserDTO::class);

            expect($meta['properties']['password']['hidden'])->toBeTrue();
            expect($meta['properties']['email']['hidden'])->toBeFalse();
        });

        it('detects map_from from attribute', function () {
            DataTransferObject::flushMetadataCache();
            $meta = DtoMetadataResolver::resolve(CreateUserDTO::class);

            expect($meta['properties']['phone']['map_from'])->toBe('phone_number');
        });

        it('detects cast from attribute', function () {
            DataTransferObject::flushMetadataCache();
            $meta = DtoMetadataResolver::resolve(CreateUserDTO::class);

            expect($meta['properties']['tags']['cast'])->toBe('array');
        });
    });

    describe('OpenApiSchemaGenerator', function () {
        it('generates schema for CreateUserDTO', function () {
            $schema = OpenApiSchemaGenerator::generate(CreateUserDTO::class);

            expect($schema)->toBeArray();
            expect($schema)->toHaveKey('type');
            expect($schema['type'])->toBe('object');
            expect($schema)->toHaveKey('properties');
            expect($schema)->not->toHaveKey('required'); // all fields have defaults or are nullable
        });

        it('generates required fields for MinimalDTO', function () {
            $schema = OpenApiSchemaGenerator::generate(MinimalDTO::class);

            expect($schema['required'])->toContain('name');
            expect($schema['required'])->toContain('value');
        });

        it('excludes hidden properties from schema', function () {
            $schema = OpenApiSchemaGenerator::generate(CreateUserDTO::class);

            expect($schema['properties'])->not->toHaveKey('password');
        });

        it('includes nullable flag for nullable properties', function () {
            $schema = OpenApiSchemaGenerator::generate(CreateUserDTO::class);

            expect($schema['properties']['phone']['nullable'])->toBeTrue();
        });

        it('infers string type for string properties', function () {
            $schema = OpenApiSchemaGenerator::generate(CreateUserDTO::class);

            expect($schema['properties']['email']['type'])->toBe('string');
            expect($schema['properties']['email']['format'])->toBe('email');
        });
    });

    describe('DTOException named constructors', function () {
        it('invalidCast creates exception with property info', function () {
            $ex = DTOException::invalidCast('age', 'integer', 'not_a_number');
            expect($ex->getMessage())->toContain('age');
            expect($ex->getMessage())->toContain('integer');
        });

        it('invalidJson creates exception with JSON error', function () {
            $ex = DTOException::invalidJson('data', 'Syntax error');
            expect($ex->getMessage())->toContain('data');
            expect($ex->getMessage())->toContain('Syntax error');
        });
    });

    describe('partial update semantics', function () {
        it('fromPartialArray creates DTO with defaults for missing fields', function () {
            $dto = CreateUserDTO::fromPartialArray([
                'name' => 'Updated',
            ], validatePresent: false);

            expect($dto->name)->toBe('Updated');
            expect($dto->status)->toBe('active'); // default
        });

        it('validatePartialArray relaxes required to sometimes', function () {
            // Should not throw even though email is missing
            $result = CreateUserDTO::validatePartialArray([
                'name' => 'Test',
            ]);

            expect($result)->toBeArray();
            expect($result['name'])->toBe('Test');
        });
    });
});
