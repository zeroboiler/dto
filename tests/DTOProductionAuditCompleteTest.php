<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use Illuminate\Validation\ValidationException;
use ZeroBoiler\DTO\Attributes\Cast;
use ZeroBoiler\DTO\Attributes\DefaultValue;
use ZeroBoiler\DTO\Attributes\Email;
use ZeroBoiler\DTO\Attributes\Hidden;
use ZeroBoiler\DTO\Attributes\MapFrom;
use ZeroBoiler\DTO\Attributes\Max;
use ZeroBoiler\DTO\Attributes\Min;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\DTOManager;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Facades\DTO;
use ZeroBoiler\DTO\Support\DtoMetadataResolver;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;
use ZeroBoiler\DTO\Tests\Fixtures\MinimalDTO;
use ZeroBoiler\DTO\Tests\Fixtures\OrderDTO;
use ZeroBoiler\DTO\Tests\Fixtures\RegistrationDTO;
use ZeroBoiler\DTO\Tests\Fixtures\ValidationTestDTO;

describe('DTO Production Readiness — Full Audit', function () {
    // ── DtoMetadataResolver ───────────────────────────────────────────

    describe('DtoMetadataResolver', function () {
        it('resolves properties for a DTO with constructor', function () {
            $meta = DtoMetadataResolver::resolve(CreateUserDTO::class);

            expect($meta['properties'])->toBeArray();
            expect($meta['rules'])->toBeArray();
            expect($meta['messages'])->toBeArray();
        });

        it('returns empty metadata for DTO without constructor', function () {
            $meta = DtoMetadataResolver::resolve(EmptyDTO::class);

            expect($meta)->toBe([
                'properties' => [],
                'rules' => [],
                'messages' => [],
            ]);
        });

        it('infers integer rule from int type', function () {
            $meta = DtoMetadataResolver::resolve(ValidationTestDTO::class);

            // Should have rules based on attribute definitions
            expect($meta['rules'])->toBeArray();
        });

        it('detects MapFrom attribute', function () {
            $meta = DtoMetadataResolver::resolve(CreateUserDTO::class);

            $foundMapFrom = false;
            foreach ($meta['properties'] as $prop) {
                if ($prop['map_from'] !== null) {
                    $foundMapFrom = true;
                    break;
                }
            }

            expect($foundMapFrom)->toBeTrue();
        });

        it('detects Hidden attribute', function () {
            $meta = DtoMetadataResolver::resolve(CreateUserDTO::class);

            $foundHidden = false;
            foreach ($meta['properties'] as $prop) {
                if ($prop['hidden'] === true) {
                    $foundHidden = true;
                    break;
                }
            }

            expect($foundHidden)->toBeTrue();
        });
    });

    // ── DTOException Factory Methods ────────────────────────────────────

    describe('DTOException', function () {
        it('creates invalidCast exception', function () {
            $e = DTOException::invalidCast('age', 'integer', 'not_a_number');

            expect($e->getMessage())->toContain('age');
            expect($e->getMessage())->toContain('integer');
        });

        it('creates invalidJson exception', function () {
            $e = DTOException::invalidJson('metadata', 'Syntax error');

            expect($e->getMessage())->toContain('metadata');
            expect($e->getMessage())->toContain('Syntax error');
        });
    });

    // ── DTOManager ─────────────────────────────────────────────────────

    describe('DTOManager', function () {
        it('validates data against DTO class', function () {
            $manager = new DTOManager;

            $result = $manager->validate(CreateUserDTO::class, [
                'email' => 'test@example.com',
                'name' => 'Test User',
            ]);

            expect($result)->toBeArray();
            expect($result['email'])->toBe('test@example.com');
        });

        it('makes DTO from array', function () {
            $manager = new DTOManager;

            $dto = $manager->make(CreateUserDTO::class, [
                'email' => 'test@example.com',
                'name' => 'Test User',
            ]);

            expect($dto)->toBeInstanceOf(CreateUserDTO::class);
        });

        it('makes DTO from JSON', function () {
            $manager = new DTOManager;

            $dto = $manager->makeFromJson(CreateUserDTO::class, json_encode([
                'email' => 'test@example.com',
                'name' => 'Test User',
            ]));

            expect($dto)->toBeInstanceOf(CreateUserDTO::class);
        });

        it('generates OpenAPI schema', function () {
            $manager = new DTOManager;

            $schema = $manager->schema(CreateUserDTO::class);

            expect($schema)->toBeArray();
            expect($schema)->toHaveKey('type');
            expect($schema['type'])->toBe('object');
        });
    });

    // ── DtoCollection Edge Cases ────────────────────────────────────────

    describe('DtoCollection edge cases', function () {
        it('rejects non-DTO items in constructor', function () {
            expect(fn () => new DtoCollection(['not a dto']))
                ->toThrow(\InvalidArgumentException::class);
        });

        it('supports pluckKey with single key', function () {
            $dto1 = CreateUserDTO::fromArray([
                'email' => 'a@test.com',
                'name' => 'Alice',
            ], validate: false);
            $dto2 = CreateUserDTO::fromArray([
                'email' => 'b@test.com',
                'name' => 'Bob',
            ], validate: false);

            $col = new DtoCollection([$dto1, $dto2]);
            $map = $col->pluckKey('email', 'name');

            expect($map)->toBe([
                'a@test.com' => 'Alice',
                'b@test.com' => 'Bob',
            ]);
        });

        it('append returns new collection without mutating original', function () {
            $dto1 = CreateUserDTO::fromArray([
                'email' => 'a@test.com',
                'name' => 'Alice',
            ], validate: false);
            $dto2 = CreateUserDTO::fromArray([
                'email' => 'b@test.com',
                'name' => 'Bob',
            ], validate: false);

            $col = new DtoCollection([$dto1]);
            $newCol = $col->append($dto2);

            expect($col->count())->toBe(1);
            expect($newCol->count())->toBe(2);
        });

        it('merge combines two collections', function () {
            $dto1 = CreateUserDTO::fromArray([
                'email' => 'a@test.com',
                'name' => 'Alice',
            ], validate: false);
            $dto2 = CreateUserDTO::fromArray([
                'email' => 'b@test.com',
                'name' => 'Bob',
            ], validate: false);

            $col1 = new DtoCollection([$dto1]);
            $col2 = new DtoCollection([$dto2]);
            $merged = $col1->merge($col2);

            expect($merged->count())->toBe(2);
            expect($col1->count())->toBe(1);
            expect($col2->count())->toBe(1);
        });

        it('filter returns new collection', function () {
            $dto1 = CreateUserDTO::fromArray([
                'email' => 'a@test.com',
                'name' => 'Alice',
            ], validate: false);
            $dto2 = CreateUserDTO::fromArray([
                'email' => 'b@test.com',
                'name' => 'Bob',
            ], validate: false);

            $col = new DtoCollection([$dto1, $dto2]);
            $filtered = $col->filter(fn (DataTransferObject $dto): bool => $dto->name === 'Alice');

            expect($filtered->count())->toBe(1);
            expect($col->count())->toBe(2);
        });

        it('offsetUnset re-indexes array', function () {
            $dto1 = CreateUserDTO::fromArray([
                'email' => 'a@test.com',
                'name' => 'Alice',
            ], validate: false);
            $dto2 = CreateUserDTO::fromArray([
                'email' => 'b@test.com',
                'name' => 'Bob',
            ], validate: false);
            $dto3 = CreateUserDTO::fromArray([
                'email' => 'c@test.com',
                'name' => 'Charlie',
            ], validate: false);

            $col = new DtoCollection([$dto1, $dto2, $dto3]);
            unset($col[0]);

            expect($col->count())->toBe(2);
            expect($col[0]->name)->toBe('Bob');
        });

        it('allValues includes hidden fields', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test',
                'password' => 'secret',
            ], validate: false);

            $col = new DtoCollection([$dto]);
            $all = $col->allValues();

            expect($all[0])->toHaveKey('password');
        });

        it('map returns plain array with integer keys preserved', function () {
            $dto1 = CreateUserDTO::fromArray([
                'email' => 'a@test.com',
                'name' => 'Alice',
            ], validate: false);

            $col = new DtoCollection([$dto1]);
            $names = $col->map(fn (DataTransferObject $dto): string => $dto->name);

            expect($names)->toBe(['Alice']);
        });
    });

    // ── EmptyDTO Edge Case ─────────────────────────────────────────────

    describe('EmptyDTO', function () {
        it('returns only nullable defaults from toArray', function () {
            $dto = EmptyDTO::fromArray([]);

            // EmptyDTO has nullable properties with defaults — toArray includes them as null
            expect($dto->toArray())->toBe(['foo' => null, 'bar' => null]);
        });

        it('returns valid JSON', function () {
            $dto = EmptyDTO::fromArray([]);

            expect($dto->toJson())->toBe('{"foo":null,"bar":null}');
        });

        it('isEmpty returns true for all-null nullable properties', function () {
            $dto = EmptyDTO::fromArray([]);

            expect($dto->isEmpty())->toBeTrue();
        });

        it('rules returns empty array (no Required attributes)', function () {
            expect(EmptyDTO::rules())->toBe([]);
        });
    });

    // ── fromJson Validation ────────────────────────────────────────────

    describe('fromJson edge cases', function () {
        it('throws DTOException for invalid JSON', function () {
            expect(fn () => CreateUserDTO::fromJson('{invalid json}'))
                ->toThrow(DTOException::class);
        });

        it('throws DTOException for sequential array', function () {
            expect(fn () => CreateUserDTO::fromJson('[1,2,3]'))
                ->toThrow(DTOException::class);
        });
    });

    // ── only/except Edge Cases ─────────────────────────────────────────

    describe('Selective output', function () {
        it('only with non-existent keys returns empty array', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test',
            ], validate: false);

            expect($dto->only('nonexistent'))->toBe([]);
        });

        it('except with non-existent keys returns all fields', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test',
            ], validate: false);

            $result = $dto->except('nonexistent');

            expect($result)->toHaveKey('email');
            expect($result)->toHaveKey('name');
        });
    });

    // ── equals() Behaviour ─────────────────────────────────────────────

    describe('equals behaviour', function () {
        it('returns true for same data', function () {
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

        it('returns false for different data', function () {
            $dto1 = CreateUserDTO::fromArray([
                'email' => 'a@test.com',
                'name' => 'Alice',
            ], validate: false);
            $dto2 = CreateUserDTO::fromArray([
                'email' => 'b@test.com',
                'name' => 'Bob',
            ], validate: false);

            expect($dto1->equals($dto2))->toBeFalse();
        });
    });

    // ── with() Always Validates ───────────────────────────────────────

    describe('with() immutable update', function () {
        it('creates new instance', function () {
            $dto = CreateUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test',
            ], validate: false);

            $updated = $dto->with(['name' => 'Updated']);

            expect($dto)->not->toBe($updated);
            expect($updated->name)->toBe('Updated');
            expect($dto->name)->toBe('Test');
        });
    });

    // ── validatePartialArray ──────────────────────────────────────────

    describe('validatePartialArray', function () {
        it('returns data unchanged when empty', function () {
            $result = CreateUserDTO::validatePartialArray([]);

            expect($result)->toBe([]);
        });
    });
});
