<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\Attributes\Cast;
use ZeroBoiler\DTO\Attributes\Collection;
use ZeroBoiler\DTO\Attributes\DefaultValue;
use ZeroBoiler\DTO\Attributes\Email;
use ZeroBoiler\DTO\Attributes\Hidden;
use ZeroBoiler\DTO\Attributes\MapFrom;
use ZeroBoiler\DTO\Attributes\Max;
use ZeroBoiler\DTO\Attributes\Min;
use ZeroBoiler\DTO\Attributes\NestedArray;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\Attributes\Url;
use ZeroBoiler\DTO\Casts\DTOCast;
use ZeroBoiler\DTO\Contracts\FromRequestDTO;
use ZeroBoiler\DTO\Contracts\ValidationAttribute;
use ZeroBoiler\DTO\Contracts\ValidatableDTO;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DTOManager;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Support\DtoMetadataResolver;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;
use ZeroBoiler\DTO\Tests\Fixtures\MinimalDTO;

describe('DTO Production Quality Audit', function () {
    describe('Strict types enforcement', function () {
        it('all source files have declare(strict_types=1)', function () {
            $srcDir = dirname(__DIR__, 2).'/src';
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($srcDir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );

            $violations = [];
            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $content = $file->getContents();
                    if (! str_contains($content, 'declare(strict_types=1)')) {
                        $violations[] = $file->getPathname();
                    }
                }
            }

            expect($violations)->toBeEmpty(
                'All PHP files must declare strict_types=1. Violations: '.implode(', ', $violations)
            );
        });
    });

    describe('Final classes enforcement', function () {
        it('all service and support classes are final', function () {
            $classes = [
                DtoCollection::class,
                DTOManager::class,
                DtoMetadataResolver::class,
                DTOCast::class,
                DTOException::class,
            ];

            foreach ($classes as $class) {
                $ref = new ReflectionClass($class);
                expect($ref->isFinal())->toBeTrue("{$class} must be final");
            }
        });

        it('all validation attributes are final', function () {
            $attributes = [
                Required::class,
                Email::class,
                Max::class,
                Min::class,
                Url::class,
                Hidden::class,
                MapFrom::class,
                Cast::class,
                DefaultValue::class,
                NestedArray::class,
                Collection::class,
            ];

            foreach ($attributes as $class) {
                $ref = new ReflectionClass($class);
                expect($ref->isFinal())->toBeTrue("{$class} must be final");
            }
        });
    });

    describe('Interface implementations', function () {
        it('DataTransferObject implements required interfaces', function () {
            expect(DataTransferObject::class)->toImplement(FromRequestDTO::class);
            expect(DataTransferObject::class)->toImplement(ValidatableDTO::class);
        });

        it('metadata attributes that implement ValidationAttribute', function () {
            $attributes = [
                NestedArray::class,
                Collection::class,
            ];

            foreach ($attributes as $class) {
                expect($class)->toImplement(ValidationAttribute::class);
            }
        });

        it('DTOCast implements CastsAttributes', function () {
            expect(DTOCast::class)->toImplement(\Illuminate\Contracts\Database\Eloquent\CastsAttributes::class);
        });
    });

    describe('DtoCollection ArrayAccess behavior', function () {
        it('offsetUnset re-indexes to prevent gaps', function () {
            $dto = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'x'], validate: false);
            $dto2 = MinimalDTO::fromArray(['name' => 'Bob', 'value' => 'y'], validate: false);
            $dto3 = MinimalDTO::fromArray(['name' => 'Charlie', 'value' => 'z'], validate: false);

            $col = new DtoCollection([$dto, $dto2, $dto3]);
            $col->offsetUnset(0); // Remove first

            // After re-index: indices are 0, 1 (not 1, 2)
            expect($col->offsetExists(0))->toBeTrue();
            expect($col->offsetExists(1))->toBeTrue();
            expect($col->offsetExists(2))->toBeFalse();
            expect($col->count())->toBe(2);
        });

        it('push appends and returns fluent self', function () {
            $dto = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'x'], validate: false);
            $dto2 = MinimalDTO::fromArray(['name' => 'Bob', 'value' => 'y'], validate: false);

            $col = new DtoCollection([$dto]);
            $result = $col->push($dto2);

            expect($result)->toBe($col); // fluent
            expect($col->count())->toBe(2);
        });

        it('filter returns new collection without modifying original', function () {
            $dto1 = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'x'], validate: false);
            $dto2 = MinimalDTO::fromArray(['name' => 'Bob', 'value' => 'y'], validate: false);

            $col = new DtoCollection([$dto1, $dto2]);
            $filtered = $col->filter(fn (DataTransferObject $d): bool => $d->name === 'Alice');

            expect($filtered)->not->toBe($col); // new instance
            expect($filtered->count())->toBe(1);
            expect($col->count())->toBe(2); // original unchanged
        });
    });

    describe('DTOException named constructors', function () {
        it('invalidCast includes property, type, and debug type', function () {
            $ex = DTOException::invalidCast('age', 'integer', 'not_a_number');

            expect($ex->getMessage())->toContain('age');
            expect($ex->getMessage())->toContain('integer');
        });

        it('invalidJson includes property and error', function () {
            $ex = DTOException::invalidJson('config', 'Syntax error');

            expect($ex->getMessage())->toContain('config');
            expect($ex->getMessage())->toContain('Syntax error');
        });
    });

    describe('EmptyDTO edge cases', function () {
        it('isEmpty returns true for empty DTO', function () {
            $dto = EmptyDTO::fromArray([], validate: false);

            expect($dto->isEmpty())->toBeTrue();
            expect($dto->isNotEmpty())->toBeFalse();
        });

        it('toArray returns empty array for empty DTO', function () {
            $dto = EmptyDTO::fromArray([], validate: false);

            expect($dto->toArray())->toBe([]);
        });
    });

    describe('Hidden field behavior', function () {
        it('toArray excludes hidden fields', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Alice',
                'password' => 'secret123',
            ], validate: false);

            $arr = $dto->toArray();

            expect($arr)->not->toHaveKey('password');
            expect($arr)->toHaveKey('email');
            expect($arr)->toHaveKey('name');
        });

        it('allValues includes hidden fields', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Alice',
                'password' => 'secret123',
            ], validate: false);

            $all = $dto->allValues();

            expect($all)->toHaveKey('password');
            expect($all['password'])->toBe('secret123');
        });

        it('only respects hidden exclusion', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Alice',
                'password' => 'secret',
            ], validate: false);

            // only() with hidden field returns empty for that field
            $result = $dto->only('password');
            expect($result)->toBe([]);
        });
    });

    describe('with() immutability', function () {
        it('returns new instance', function () {
            $dto = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'x'], validate: false);
            $updated = $dto->with(['name' => 'Bob']);

            expect($updated)->not->toBe($dto);
            expect($dto->name)->toBe('Alice');
            expect($updated->name)->toBe('Bob');
        });
    });

    describe('equals() comparison', function () {
        it('returns true for identical DTOs', function () {
            $dto1 = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'x'], validate: false);
            $dto2 = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'x'], validate: false);

            expect($dto1->equals($dto2))->toBeTrue();
        });

        it('returns false for different DTOs', function () {
            $dto1 = MinimalDTO::fromArray(['name' => 'Alice', 'value' => 'x'], validate: false);
            $dto2 = MinimalDTO::fromArray(['name' => 'Bob', 'value' => 'y'], validate: false);

            expect($dto1->equals($dto2))->toBeFalse();
        });
    });

    describe('fromJson edge cases', function () {
        it('rejects sequential JSON arrays', function () {
            expect(fn () => MinimalDTO::fromJson('["Alice","Bob"]'))
                ->toThrow(DTOException::class);
        });

        it('rejects invalid JSON', function () {
            expect(fn () => MinimalDTO::fromJson('{invalid json'))
                ->toThrow(DTOException::class);
        });
    });
});
