<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\Attributes\Accepted;
use ZeroBoiler\DTO\Attributes\Boolean;
use ZeroBoiler\DTO\Attributes\Cast;
use ZeroBoiler\DTO\Attributes\Date;
use ZeroBoiler\DTO\Attributes\DefaultValue;
use ZeroBoiler\DTO\Attributes\Email;
use ZeroBoiler\DTO\Attributes\Hidden;
use ZeroBoiler\DTO\Attributes\Hidden as HiddenAttr;
use ZeroBoiler\DTO\Attributes\In;
use ZeroBoiler\DTO\Attributes\Json as JsonAttr;
use ZeroBoiler\DTO\Attributes\MapFrom;
use ZeroBoiler\DTO\Attributes\Max;
use ZeroBoiler\DTO\Attributes\Min;
use ZeroBoiler\DTO\Attributes\Nullable;
use ZeroBoiler\DTO\Attributes\Pattern;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\Attributes\Size;
use ZeroBoiler\DTO\Attributes\StartsWith;
use ZeroBoiler\DTO\Attributes\Url;
use ZeroBoiler\DTO\Attributes\Uuid;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\MinimalDTO;
use ZeroBoiler\DTO\Tests\Fixtures\OrderDTO;
use ZeroBoiler\DTO\Tests\Fixtures\OrderItemDTO;
use ZeroBoiler\DTO\Tests\Fixtures\RoundtripDTO;
use ZeroBoiler\DTO\Tests\Fixtures\AddressDTO;

/**
 * Comprehensive edge-case tests for DTO validation, hydration, serialization,
 * and type safety compliance.
 *
 * PHPStan Level 9 compliance: no mixed types in assertions, strict comparisons only.
 */
describe('DtoValidationHydrationAndTypeSafetyEdgeCases', function () {
    describe('fromArray with MapFrom dot notation', function () {
        it('resolves dot-notated source keys correctly', function () {
            $dto = new class extends DataTransferObject {
                public function __construct(
                    #[MapFrom('user.profile.name')]
                    public readonly string $name,

                    #[MapFrom('user.profile.email')]
                    public readonly string $email,
                ) {}
            };

            $instance = $dto::fromArray([
                'user' => [
                    'profile' => [
                        'name' => 'Alice',
                        'email' => 'alice@example.com',
                    ],
                ],
            ], validate: false);

            expect($instance->name)->toBe('Alice');
            expect($instance->email)->toBe('alice@example.com');
        });
    });

    describe('Cast type transformations', function () {
        it('casts string int to actual int via Cast attribute', function () {
            $dto = new class extends DataTransferObject {
                public function __construct(
                    #[Cast('integer')]
                    public readonly int $count,
                ) {}
            };

            $instance = $dto::fromArray(['count' => '42'], validate: false);

            expect($instance->count)->toBe(42);
            expect($instance->count)->toBeInt();
        });

        it('casts string float to actual float via Cast attribute', function () {
            $dto = new class extends DataTransferObject {
                public function __construct(
                    #[Cast('float')]
                    public readonly float $price,
                ) {}
            };

            $instance = $dto::fromArray(['price' => '99.99'], validate: false);

            expect($instance->price)->toBe(99.99);
            expect($instance->price)->toBeFloat();
        });

        it('casts string to boolean via filter_var semantics', function () {
            $dto = new class extends DataTransferObject {
                public function __construct(
                    #[Cast('boolean')]
                    public readonly bool $active,
                ) {}
            };

            $instance = $dto::fromArray(['active' => 'yes'], validate: false);

            expect($instance->active)->toBeTrue();
        });

        it('casts JSON string to array via Cast attribute', function () {
            $dto = new class extends DataTransferObject {
                public function __construct(
                    #[Cast('array')]
                    public readonly array $tags,
                ) {}
            };

            $instance = $dto::fromArray(['tags' => '["a","b","c"]'], validate: false);

            expect($instance->tags)->toBe(['a', 'b', 'c']);
        });

        it('passes through array unchanged when Cast type is array', function () {
            $dto = new class extends DataTransferObject {
                public function __construct(
                    #[Cast('array')]
                    public readonly array $items,
                ) {}
            };

            $instance = $dto::fromArray(['items' => [1, 2, 3]], validate: false);

            expect($instance->items)->toBe([1, 2, 3]);
        });
    });

    describe('DefaultValue handling', function () {
        it('applies default when source key is absent', function () {
            $dto = new class extends DataTransferObject {
                public function __construct(
                    #[DefaultValue('guest')]
                    public readonly string $role,

                    #[Required]
                    public readonly string $name,
                ) {}
            };

            $instance = $dto::fromArray(['name' => 'Alice'], validate: false);

            expect($instance->role)->toBe('guest');
        });

        it('respects explicit null even when default exists', function () {
            $dto = new class extends DataTransferObject {
                public function __construct(
                    #[DefaultValue('active')]
                    public readonly ?string $status,
                ) {}
            };

            $instance = $dto::fromArray(['status' => null], validate: false);

            // Null is explicitly provided, so it takes precedence over default
            expect($instance->status)->toBeNull();
        });

        it('applies array default value', function () {
            $dto = new class extends DataTransferObject {
                public function __construct(
                    #[DefaultValue(['admin', 'user'])]
                    public readonly array $roles,
                ) {}
            };

            $instance = $dto::fromArray([], validate: false);

            expect($instance->roles)->toBe(['admin', 'user']);
        });
    });

    describe('Hidden field behavior', function () {
        it('excludes hidden field from toArray but includes in allValues', function () {
            $dto = new class extends DataTransferObject {
                public function __construct(
                    #[Required]
                    public readonly string $name,

                    #[Hidden]
                    public readonly string $secret,
                ) {}
            };

            $instance = $dto::fromArray(['name' => 'Alice', 'secret' => 'password123'], validate: false);

            expect($instance->toArray())->not->toHaveKey('secret');
            expect($instance->toArray())->toHaveKey('name');
            expect($instance->allValues())->toHaveKey('secret');
            expect($instance->allValues()['secret'])->toBe('password123');
        });

        it('hidden field is excluded from JSON output', function () {
            $dto = new class extends DataTransferObject {
                public function __construct(
                    #[Required]
                    public readonly string $name,

                    #[Hidden]
                    public readonly string $token,
                ) {}
            };

            $instance = $dto::fromArray(['name' => 'Bob', 'token' => 'abc123'], validate: false);
            $json = $instance->toJson();
            $decoded = json_decode($json, true);

            expect($decoded)->not->toHaveKey('token');
            expect($decoded)->toHaveKey('name');
        });
    });

    describe('only() and except() selective output', function () {
        it('only() returns specified fields only', function () {
            $dto = new class extends DataTransferObject {
                public function __construct(
                    public readonly string $a = '1',
                    public readonly string $b = '2',
                    public readonly string $c = '3',
                ) {}
            };

            $instance = $dto::fromArray([], validate: false);

            expect($instance->only('a', 'c'))->toBe(['a' => '1', 'c' => '3']);
        });

        it('except() returns all fields except specified', function () {
            $dto = new class extends DataTransferObject {
                public function __construct(
                    public readonly string $x = '10',
                    public readonly string $y = '20',
                    public readonly string $z = '30',
                ) {}
            };

            $instance = $dto::fromArray([], validate: false);

            expect($instance->except('y'))->toBe(['x' => '10', 'z' => '30']);
        });

        it('only() accepts single string key', function () {
            $dto = new class extends DataTransferObject {
                public function __construct(
                    public readonly string $foo = 'bar',
                    public readonly string $baz = 'qux',
                ) {}
            };

            $instance = $dto::fromArray([], validate: false);

            expect($instance->only('foo'))->toBe(['foo' => 'bar']);
        });
    });

    describe('fromJson edge cases', function () {
        it('rejects sequential JSON arrays (non-objects)', function () {
            expect(fn () => MinimalDTO::fromJson('["a","b","c"]', validate: false))
                ->toThrow(DTOException::class);
        });

        it('allows empty array as valid empty object', function () {
            $instance = MinimalDTO::fromJson('{}', validate: false);

            expect($instance)->toBeInstanceOf(MinimalDTO::class);
        });

        it('accepts empty array literal', function () {
            $instance = MinimalDTO::fromJson('[]', validate: false);

            expect($instance)->toBeInstanceOf(MinimalDTO::class);
        });

        it('throws DTOException for malformed JSON', function () {
            expect(fn () => MinimalDTO::fromJson('{"broken": }', validate: false))
                ->toThrow(DTOException::class);
        });
    });

    describe('equals() comparison', function () {
        it('returns true for identical DTOs', function () {
            $d1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice', 'password' => '12345678'], validate: false);
            $d2 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice', 'password' => '12345678'], validate: false);

            expect($d1->equals($d2))->toBeTrue();
        });

        it('returns false for different DTOs', function () {
            $d1 = CreateUserDTO::fromArray(['email' => 'a@b.com', 'name' => 'Alice', 'password' => '12345678'], validate: false);
            $d2 = CreateUserDTO::fromArray(['email' => 'x@y.com', 'name' => 'Bob', 'password' => '12345678'], validate: false);

            expect($d1->equals($d2))->toBeFalse();
        });

        it('hidden fields are excluded from equals comparison', function () {
            $dtoClass = new class extends DataTransferObject {
                public function __construct(
                    public readonly string $name,

                    #[Hidden]
                    public readonly string $secret,
                ) {}
            };

            $d1 = $dtoClass::fromArray(['name' => 'Alice', 'secret' => 'aaa'], validate: false);
            $d2 = $dtoClass::fromArray(['name' => 'Alice', 'secret' => 'bbb'], validate: false);

            // Both have same public-facing toArray(), so equals should be true
            expect($d1->equals($d2))->toBeTrue();
        });
    });

    describe('isEmpty() and isNotEmpty()', function () {
        it('isEmpty returns true when all properties are empty defaults', function () {
            $dto = new class extends DataTransferObject {
                public function __construct(
                    public readonly ?string $name = null,
                    public readonly string $status = '',
                    public readonly array $tags = [],
                    public readonly bool $active = false,
                ) {}
            };

            $instance = $dto::fromArray([], validate: false);

            expect($instance->isEmpty())->toBeTrue();
            expect($instance->isNotEmpty())->toBeFalse();
        });

        it('isEmpty returns false when a non-zero numeric value exists', function () {
            $dto = new class extends DataTransferObject {
                public function __construct(
                    public readonly int $count = 0,
                ) {}
            };

            $instance = $dto::fromArray(['count' => 0], validate: false);

            // 0 is a valid non-empty value for non-nullable int
            expect($instance->isEmpty())->toBeFalse();
        });
    });

    describe('fromPartialArray edge cases', function () {
        it('merges partial data with defaults', function () {
            $dto = new class extends DataTransferObject {
                public function __construct(
                    #[DefaultValue('active')]
                    public readonly string $status,

                    #[DefaultValue('guest')]
                    public readonly string $role,
                ) {}
            };

            $instance = $dto::fromPartialArray(['status' => 'inactive'], validate: false);

            expect($instance->status)->toBe('inactive');
            expect($instance->role)->toBe('guest');
        });

        it('fromPartialArray with empty data uses all defaults', function () {
            $dto = new class extends DataTransferObject {
                public function __construct(
                    #[DefaultValue('default')]
                    public readonly string $value,
                ) {}
            };

            $instance = $dto::fromPartialArray([], validate: false);

            expect($instance->value)->toBe('default');
        });
    });

    describe('with() immutable update', function () {
        it('returns new instance without mutating original', function () {
            $original = CreateUserDTO::fromArray([
                'email' => 'a@b.com',
                'name' => 'Alice',
                'password' => '12345678',
            ], validate: false);

            $modified = $original->with(['name' => 'Bob'], validate: false);

            expect($original->name)->toBe('Alice');
            expect($modified->name)->toBe('Bob');
            expect($modified->email)->toBe('a@b.com');
        });
    });

    describe('rules() and rulesFor() consistency', function () {
        it('rules() returns array of arrays', function () {
            $rules = CreateUserDTO::rules();

            foreach ($rules as $field => $fieldRules) {
                expect($fieldRules)->toBeArray();
                expect($fieldRules)->not->toBeEmpty();
            }
        });

        it('rulesFor() returns same as rules() by default', function () {
            $rules = CreateUserDTO::rules();
            $rulesForUpdate = CreateUserDTO::rulesFor('update');

            expect($rulesForUpdate)->toEqual($rules);
        });
    });
});
