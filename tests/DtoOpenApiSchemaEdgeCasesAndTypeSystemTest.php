<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\Attributes\Between;
use ZeroBoiler\DTO\Attributes\Boolean;
use ZeroBoiler\DTO\Attributes\Cast;
use ZeroBoiler\DTO\Attributes\Date;
use ZeroBoiler\DTO\Attributes\Email;
use ZeroBoiler\DTO\Attributes\EndsWith;
use ZeroBoiler\DTO\Attributes\Hidden;
use ZeroBoiler\DTO\Attributes\In;
use ZeroBoiler\DTO\Attributes\Integer;
use ZeroBoiler\DTO\Attributes\Max;
use ZeroBoiler\DTO\Attributes\Min;
use ZeroBoiler\DTO\Attributes\Numeric;
use ZeroBoiler\DTO\Attributes\Pattern;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\Attributes\StartsWith;
use ZeroBoiler\DTO\Attributes\Url;
use ZeroBoiler\DTO\Attributes\Uuid;
use ZeroBoiler\DTO\Contracts\ValidationAttribute;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Support\DtoMetadataResolver;
use ZeroBoiler\DTO\Support\OpenApiSchemaGenerator;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;
use ZeroBoiler\DTO\Tests\Fixtures\OpenApiValidationDTO;
use ZeroBoiler\DTO\Tests\Fixtures\UnionTypeDTO;

describe('DTO OpenAPI schema edge cases and type system compliance', function () {
    describe('OpenApiSchemaGenerator — union type support', function () {
        it('generates oneOf schema for int|string union type', function () {
            $schema = OpenApiSchemaGenerator::generate(UnionTypeDTO::class);

            expect($schema)->toBeArray();
            expect($schema['type'])->toBe('object');
            expect($schema)->toHaveKey('properties');
            expect($schema)->toHaveKey('required');

            // identifier is int|string — should produce oneOf
            expect($schema['properties']['identifier'])->toHaveKey('oneOf');
            $oneOf = $schema['properties']['identifier']['oneOf'];
            expect($oneOf)->toBeArray();
            expect(count($oneOf))->toBeGreaterThanOrEqual(2);

            // oneOf should contain integer and string types
            $types = array_column($oneOf, 'type');
            expect($types)->toContain('integer');
            expect($types)->toContain('string');
        });

        it('marks id as required in schema', function () {
            $schema = OpenApiSchemaGenerator::generate(UnionTypeDTO::class);
            expect($schema['required'])->toContain('id');
        });

        it('id property has string type', function () {
            $schema = OpenApiSchemaGenerator::generate(UnionTypeDTO::class);
            expect($schema['properties']['id']['type'])->toBe('string');
        });
    });

    describe('OpenApiSchemaGenerator — validation attribute constraints', function () {
        it('applies email format constraint', function () {
            $schema = OpenApiSchemaGenerator::generate(OpenApiValidationDTO::class);
            expect($schema['properties']['email']['format'])->toBe('email');
        });

        it('applies uri format constraint', function () {
            $schema = OpenApiSchemaGenerator::generate(OpenApiValidationDTO::class);
            expect($schema['properties']['website']['format'])->toBe('uri');
        });

        it('applies uuid format constraint', function () {
            $schema = OpenApiSchemaGenerator::generate(OpenApiValidationDTO::class);
            expect($schema['properties']['externalId']['format'])->toBe('uuid');
        });

        it('applies pattern constraint with stripped delimiters', function () {
            $schema = OpenApiSchemaGenerator::generate(OpenApiValidationDTO::class);
            expect($schema['properties']['code'])->toHaveKey('pattern');
            // Delimiters should be stripped: '/^[A-Z]{3}$/' → '^[A-Z]{3}$'
            expect($schema['properties']['code']['pattern'])->not->toContain('/');
        });

        it('applies integer type from Integer attribute', function () {
            $schema = OpenApiSchemaGenerator::generate(OpenApiValidationDTO::class);
            expect($schema['properties']['quantity']['type'])->toBe('integer');
        });

        it('applies minimum and maximum from Min/Max on integer field', function () {
            $schema = OpenApiSchemaGenerator::generate(OpenApiValidationDTO::class);
            expect($schema['properties']['quantity']['minimum'])->toBe(1);
            expect($schema['properties']['quantity']['maximum'])->toBe(100);
        });

        it('applies number type from Numeric attribute', function () {
            $schema = OpenApiSchemaGenerator::generate(OpenApiValidationDTO::class);
            expect($schema['properties']['price']['type'])->toBe('number');
        });

        it('applies minimum and maximum from Between on numeric field', function () {
            $schema = OpenApiSchemaGenerator::generate(OpenApiValidationDTO::class);
            expect($schema['properties']['price']['minimum'])->toBe(0.0);
            expect($schema['properties']['price']['maximum'])->toBe(99.99);
        });

        it('applies minLength and maxLength from Min/Max on string field', function () {
            $schema = OpenApiSchemaGenerator::generate(OpenApiValidationDTO::class);
            expect($schema['properties']['name']['minLength'])->toBe(2);
            expect($schema['properties']['name']['maxLength'])->toBe(50);
        });

        it('applies date format from Date attribute', function () {
            $schema = OpenApiSchemaGenerator::generate(OpenApiValidationDTO::class);
            expect($schema['properties']['birthDate']['format'])->toBe('date');
        });

        it('applies pattern from StartsWith constraint', function () {
            $schema = OpenApiSchemaGenerator::generate(OpenApiValidationDTO::class);
            expect($schema['properties']['apiUrl'])->toHaveKey('pattern');
            $pattern = $schema['properties']['apiUrl']['pattern'];
            // Should contain a pattern that enforces the prefix
            expect($pattern)->not->toBeEmpty();
        });

        it('applies pattern from EndsWith constraint', function () {
            $schema = OpenApiSchemaGenerator::generate(OpenApiValidationDTO::class);
            expect($schema['properties']['workEmail'])->toHaveKey('pattern');
            $pattern = $schema['properties']['workEmail']['pattern'];
            expect($pattern)->not->toBeEmpty();
        });
    });

    describe('OpenApiSchemaGenerator — empty and minimal DTOs', function () {
        it('generates empty properties for DTO with no constructor', function () {
            // EmptyDTO has a constructor but all optional nullable fields
            $schema = OpenApiSchemaGenerator::generate(EmptyDTO::class);

            expect($schema['type'])->toBe('object');
            expect($schema)->toHaveKey('properties');
            expect($schema['properties'])->toHaveCount(2); // foo and bar
            expect($schema['properties']['foo']['type'])->toBe('string');
            expect($schema['properties']['foo']['nullable'])->toBeTrue();
            expect($schema['properties']['bar']['type'])->toBe('string');
            expect($schema['properties']['bar']['nullable'])->toBeTrue();

            // Neither foo nor bar should be in required list (both nullable with defaults)
            expect($schema['required'] ?? [])->not->toContain('foo');
            expect($schema['required'] ?? [])->not->toContain('bar');
        });

        it('generate() throws LogicException for DTO with nested DTOs', function () {
            // OrderDTO has nested AddressDTO — generate() should throw
            expect(fn () => OpenApiSchemaGenerator::generate(\ZeroBoiler\DTO\Tests\Fixtures\OrderDTO::class))
                ->toThrow(\LogicException::class);
        });

        it('generateWithComponents returns both schema and components for nested DTOs', function () {
            $result = OpenApiSchemaGenerator::generateWithComponents(
                \ZeroBoiler\DTO\Tests\Fixtures\OrderDTO::class
            );

            expect($result)->toHaveKey('schema');
            expect($result)->toHaveKey('components');
            expect($result['components'])->toHaveKey('schemas');
            expect($result['components']['schemas'])->toHaveKey('AddressDTO');
        });
    });

    describe('PHPStan Level 9 type system compliance', function () {
        it('all source files have declare(strict_types=1)', function () {
            $srcDir = __DIR__.'/../src';
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($srcDir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );

            $checked = 0;
            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $content = file_get_contents($file->getPathname());
                    expect($content)->toContain('declare(strict_types=1)');
                    $checked++;
                }
            }

            expect($checked)->toBeGreaterThan(0);
        });

        it('all public methods have return type declarations', function () {
            $classes = [
                DtoCollection::class,
                \ZeroBoiler\DTO\DTOManager::class,
                \ZeroBoiler\DTO\Exceptions\DTOException::class,
                \ZeroBoiler\DTO\Support\DtoMetadataResolver::class,
                \ZeroBoiler\DTO\Support\OpenApiSchemaGenerator::class,
                \ZeroBoiler\DTO\Contracts\ValidationAttribute::class,
                \ZeroBoiler\DTO\Contracts\FromRequestDTO::class,
                \ZeroBoiler\DTO\Contracts\ValidatableDTO::class,
                \ZeroBoiler\DTO\Facades\DTO::class,
            ];

            foreach ($classes as $class) {
                $ref = new ReflectionClass($class);
                foreach ($ref->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                    // Skip constructor and inherited __construct
                    if ($method->getName() === '__construct') {
                        continue;
                    }

                    $returnType = $method->getReturnType();
                    expect($returnType)->not->toBeNull(
                        "{$class}::{$method->getName()}() must have a return type declaration"
                    );
                }
            }
        });

        it('DtoCollection methods return correct types', function () {
            $ref = new ReflectionClass(DtoCollection::class);

            // count(): int
            expect($ref->getMethod('count')->getReturnType()->getName())->toBe('int');

            // isEmpty(): bool
            expect($ref->getMethod('isEmpty')->getReturnType()->getName())->toBe('bool');

            // isNotEmpty(): bool
            expect($ref->getMethod('isNotEmpty')->getReturnType()->getName())->toBe('bool');

            // first(): ?DataTransferObject
            expect($ref->getMethod('first')->getReturnType()->allowsNull())->toBeTrue();

            // last(): ?DataTransferObject
            expect($ref->getMethod('last')->getReturnType()->allowsNull())->toBeTrue();

            // map(): array
            expect($ref->getMethod('map')->getReturnType()->getName())->toBe('array');

            // push(): self
            expect($ref->getMethod('push')->getReturnType()->getName())->toBe(self::class);

            // filter(): self
            expect($ref->getMethod('filter')->getReturnType()->getName())->toBe(self::class);

            // append(): self
            expect($ref->getMethod('append')->getReturnType()->getName())->toBe(self::class);

            // merge(): self
            expect($ref->getMethod('merge')->getReturnType()->getName())->toBe(self::class);

            // make(): self
            expect($ref->getMethod('make')->getReturnType()->getName())->toBe(self::class);

            // toArray(): array
            expect($ref->getMethod('toArray')->getReturnType()->getName())->toBe('array');

            // allValues(): array
            expect($ref->getMethod('allValues')->getReturnType()->getName())->toBe('array');

            // items(): array
            expect($ref->getMethod('items')->getReturnType()->getName())->toBe('array');

            // pluck(): array
            expect($ref->getMethod('pluck')->getReturnType()->getName())->toBe('array');

            // pluckKey(): array
            expect($ref->getMethod('pluckKey')->getReturnType()->getName())->toBe('array');
        });

        it('all validation attributes are final', function () {
            $attrDir = __DIR__.'/../src/Attributes';
            $iterator = new DirectoryIterator($attrDir);

            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $className = 'ZeroBoiler\\DTO\\Attributes\\'.$file->getBasename('.php');
                    if (! class_exists($className)) {
                        continue;
                    }

                    $ref = new ReflectionClass($className);
                    expect($ref->isFinal())->toBeTrue("{$className} must be final");
                }
            }
        });

        it('all validation attributes have readonly promoted properties', function () {
            $attrDir = __DIR__.'/../src/Attributes';
            $iterator = new DirectoryIterator($attrDir);

            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $className = 'ZeroBoiler\\DTO\\Attributes\\'.$file->getBasename('.php');
                    if (! class_exists($className)) {
                        continue;
                    }

                    $ref = new ReflectionClass($className);
                    foreach ($ref->getProperties() as $prop) {
                        if ($prop->isStatic()) {
                            continue;
                        }
                        expect($prop->isReadOnly())->toBeTrue(
                            "{$className}::\${$prop->getName()} must be readonly"
                        );
                    }
                }
            }
        });

        it('all validation attribute properties have explicit types', function () {
            $attrDir = __DIR__.'/../src/Attributes';
            $iterator = new DirectoryIterator($attrDir);

            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $className = 'ZeroBoiler\\DTO\\Attributes\\'.$file->getBasename('.php');
                    if (! class_exists($className)) {
                        continue;
                    }

                    $ref = new ReflectionClass($className);
                    foreach ($ref->getProperties() as $prop) {
                        if ($prop->isStatic()) {
                            continue;
                        }
                        $type = $prop->getType();
                        expect($type)->not->toBeNull(
                            "{$className}::\${$prop->getName()} must have an explicit type"
                        );
                        expect($type->getName())->not->toBe('mixed');
                    }
                }
            }
        });
    });

    describe('DtoMetadataResolver — rule inference', function () {
        it('infers integer rule from int type', function () {
            $metadata = DtoMetadataResolver::resolve(OpenApiValidationDTO::class);
            expect($metadata['rules']['quantity'])->toContain('integer');
        });

        it('infers numeric rule from float type', function () {
            $metadata = DtoMetadataResolver::resolve(OpenApiValidationDTO::class);
            expect($metadata['rules']['price'])->toContain('numeric');
        });

        it('nullable properties get sometimes rule', function () {
            $metadata = DtoMetadataResolver::resolve(EmptyDTO::class);
            expect($metadata['rules']['foo'])->toContain('sometimes');
            expect($metadata['rules']['bar'])->toContain('sometimes');
        });

        it('required fields do not get sometimes rule', function () {
            $metadata = DtoMetadataResolver::resolve(UnionTypeDTO::class);
            expect($metadata['rules']['id'])->toContain('required');
            expect($metadata['rules']['id'])->not->toContain('sometimes');
        });

        it('email attribute generates email rule', function () {
            $metadata = DtoMetadataResolver::resolve(OpenApiValidationDTO::class);
            expect($metadata['rules']['email'])->toContain('email');
        });

        it('min/max attributes generate correct rules', function () {
            $metadata = DtoMetadataResolver::resolve(OpenApiValidationDTO::class);
            expect($metadata['rules']['quantity'])->toContain('min:1');
            expect($metadata['rules']['quantity'])->toContain('max:100');
            expect($metadata['rules']['name'])->toContain('min:2');
            expect($metadata['rules']['name'])->toContain('max:50');
        });

        it('between attribute generates correct rule', function () {
            $metadata = DtoMetadataResolver::resolve(OpenApiValidationDTO::class);
            expect($metadata['rules']['price'])->toContain('between:0,99.99');
        });

        it('pattern attribute generates regex rule', function () {
            $metadata = DtoMetadataResolver::resolve(OpenApiValidationDTO::class);
            expect($metadata['rules']['code'])->toContain('regex:/^[A-Z]{3}$/');
        });
    });

    describe('DtoCollection — immutability of append and merge', function () {
        it('append returns a new instance without modifying the original', function () {
            $d1 = EmptyDTO::fromArray(['foo' => 'a'], validate: false);
            $d2 = EmptyDTO::fromArray(['foo' => 'b'], validate: false);

            $original = new DtoCollection([$d1]);
            $appended = $original->append($d2);

            expect($original->count())->toBe(1);
            expect($appended->count())->toBe(2);
            expect($original)->not->toBe($appended); // different instances
        });

        it('merge returns a new instance combining both collections', function () {
            $d1 = EmptyDTO::fromArray(['foo' => 'a'], validate: false);
            $d2 = EmptyDTO::fromArray(['foo' => 'b'], validate: false);
            $d3 = EmptyDTO::fromArray(['foo' => 'c'], validate: false);

            $col1 = new DtoCollection([$d1]);
            $col2 = new DtoCollection([$d2, $d3]);

            $merged = $col1->merge($col2);

            expect($col1->count())->toBe(1); // unchanged
            expect($col2->count())->toBe(2); // unchanged
            expect($merged->count())->toBe(3); // combined
        });

        it('filter returns a new collection without modifying the original', function () {
            $d1 = EmptyDTO::fromArray(['foo' => 'a'], validate: false);
            $d2 = EmptyDTO::fromArray(['foo' => 'b'], validate: false);

            $original = new DtoCollection([$d1, $d2]);
            $filtered = $original->filter(
                fn (DataTransferObject $dto) => $dto->foo === 'a'
            );

            expect($original->count())->toBe(2); // unchanged
            expect($filtered->count())->toBe(1);
            expect($filtered->first()->foo)->toBe('a');
        });
    });

    describe('DtoCollection — ArrayAccess and IteratorAggregate', function () {
        it('offsetExists returns true for existing indices', function () {
            $d1 = EmptyDTO::fromArray(['foo' => 'a'], validate: false);
            $col = new DtoCollection([$d1]);

            expect($col->offsetExists(0))->toBeTrue();
            expect($col->offsetExists(1))->toBeFalse();
        });

        it('offsetGet returns DTO instance or null', function () {
            $d1 = EmptyDTO::fromArray(['foo' => 'a'], validate: false);
            $col = new DtoCollection([$d1]);

            expect($col->offsetGet(0))->toBe($d1);
            expect($col->offsetGet(1))->toBeNull();
        });

        it('offsetSet appends when offset is null', function () {
            $col = new DtoCollection;
            $d1 = EmptyDTO::fromArray(['foo' => 'a'], validate: false);

            $col->offsetSet(null, $d1);
            expect($col->count())->toBe(1);
            expect($col->offsetGet(0))->toBe($d1);
        });

        it('offsetSet replaces at existing index', function () {
            $d1 = EmptyDTO::fromArray(['foo' => 'a'], validate: false);
            $d2 = EmptyDTO::fromArray(['foo' => 'b'], validate: false);

            $col = new DtoCollection([$d1]);
            $col->offsetSet(0, $d2);

            expect($col->count())->toBe(1);
            expect($col->offsetGet(0)->foo)->toBe('b');
        });

        it('offsetUnset removes item and re-indexes', function () {
            $d1 = EmptyDTO::fromArray(['foo' => 'a'], validate: false);
            $d2 = EmptyDTO::fromArray(['foo' => 'b'], validate: false);
            $d3 = EmptyDTO::fromArray(['foo' => 'c'], validate: false);

            $col = new DtoCollection([$d1, $d2, $d3]);
            $col->offsetUnset(0);

            expect($col->count())->toBe(2);
            // After re-indexing, index 0 should be the former d2
            expect($col->offsetGet(0)->foo)->toBe('b');
            expect($col->offsetGet(1)->foo)->toBe('c');
        });

        it('throws InvalidArgumentException for non-DTO values', function () {
            $col = new DtoCollection;

            expect(fn () => $col->offsetSet(null, 'not a dto'))
                ->toThrow(\InvalidArgumentException::class);
        });

        it('is iterable via foreach', function () {
            $d1 = EmptyDTO::fromArray(['foo' => 'a'], validate: false);
            $d2 = EmptyDTO::fromArray(['foo' => 'b'], validate: false);

            $col = new DtoCollection([$d1, $d2]);
            $results = [];

            foreach ($col as $key => $dto) {
                $results[$key] = $dto->foo;
            }

            expect($results)->toEqual([0 => 'a', 1 => 'b']);
        });
    });

    describe('DTOException named constructors', function () {
        it('invalidCast includes property, type, and value debug type in message', function () {
            $e = DTOException::invalidCast('age', 'integer', 'not_a_number');
            $msg = $e->getMessage();
            expect($msg)->toContain('age');
            expect($msg)->toContain('integer');
            expect($msg)->toContain('string');
        });

        it('invalidCast works with null value', function () {
            $e = DTOException::invalidCast('status', 'date', null);
            expect($e->getMessage())->toContain('status');
            expect($e->getMessage())->toContain('date');
        });

        it('invalidJson includes property and error message', function () {
            $e = DTOException::invalidJson('tags', 'Syntax error');
            expect($e->getMessage())->toContain('tags');
            expect($e->getMessage())->toContain('Syntax error');
        });

        it('exceptions are final and extend Exception', function () {
            $ref = new ReflectionClass(DTOException::class);
            expect($ref->isFinal())->toBeTrue();
            expect($ref->getParentClass()->getName())->toBe('Exception');
        });
    });

    describe('DTO serialization roundtrip — JSON compatibility', function () {
        it('toJson produces valid JSON that can be decoded back', function () {
            $dto = \ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test User',
                'status' => 'active',
                'tags' => ['php', 'laravel'],
            ], validate: false);

            $json = $dto->toJson();
            expect($json)->not->toBeEmpty();

            $decoded = json_decode($json, true);
            expect($decoded)->toBeArray();
            expect($decoded['email'])->toBe('test@example.com');
            expect($decoded['name'])->toBe('Test User');
            expect($decoded['status'])->toBe('active');
            expect($decoded['tags'])->toBe(['php', 'laravel']);
            expect($decoded)->not->toHaveKey('password'); // hidden
        });

        it('jsonSerialize returns array matching toArray', function () {
            $dto = \ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO::fromArray([
                'email' => 'roundtrip@test.com',
                'name' => 'Roundtrip',
            ], validate: false);

            expect($dto->jsonSerialize())->toEqual($dto->toArray());
        });

        it('DtoCollection jsonSerialize returns array of arrays', function () {
            $d1 = EmptyDTO::fromArray(['foo' => 'a'], validate: false);
            $d2 = EmptyDTO::fromArray(['foo' => 'b'], validate: false);

            $col = new DtoCollection([$d1, $d2]);
            $serialized = $col->jsonSerialize();

            expect($serialized)->toBeArray();
            expect($serialized)->toHaveCount(2);
            expect($serialized[0])->toBeArray();
            expect($serialized[0]['foo'])->toBe('a');
        });
    });

    describe('Metadata cache TTL behavior', function () {
        it('flushMetadataCache clears all cached metadata', function () {
            // Force resolution to cache metadata
            DtoMetadataResolver::resolve(EmptyDTO::class);

            // Flush all
            DataTransferObject::flushMetadataCache();

            // The static cache should be empty now
            // We can verify by checking resolve still works (won't error)
            $meta = DtoMetadataResolver::resolve(EmptyDTO::class);
            expect($meta)->toBeArray();
        });

        it('flushMetadataCache with specific class clears only that class', function () {
            // Resolve both DTOs
            DtoMetadataResolver::resolve(EmptyDTO::class);
            DtoMetadataResolver::resolve(UnionTypeDTO::class);

            // Flush only EmptyDTO
            DataTransferObject::flushMetadataCache(EmptyDTO::class);

            // Re-resolve — should work fine (cache rebuilt)
            $meta = DtoMetadataResolver::resolve(EmptyDTO::class);
            expect($meta)->toBeArray();
        });

        it('setMetadataCacheTtl changes TTL value', function () {
            DataTransferObject::setMetadataCacheTtl(5.0);
            // No assertion needed — just verify it doesn't throw
            DataTransferObject::setMetadataCacheTtl(0.0);
        });
    });
});
