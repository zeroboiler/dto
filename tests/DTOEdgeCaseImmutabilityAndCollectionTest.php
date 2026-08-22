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
use ZeroBoiler\DTO\Attributes\Nullable;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\Attributes\Url;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;

// ── Test Fixtures ──────────────────────────────────────────────────

class EdgeCaseAddressDTO extends DataTransferObject
{
    public function __construct(
        #[Required, Min(5), Max(100)]
        public readonly string $street,

        #[Required]
        public readonly string $city,

        #[MapFrom('zip_code')]
        public ?string $zip = null,

        #[Hidden]
        public ?string $internalNotes = null,
    ) {}
}

class EdgeCaseProfileDTO extends DataTransferObject
{
    public function __construct(
        #[Required, Email]
        public readonly string $email,

        #[MapFrom('display_name')]
        public ?string $displayName = null,

        #[DefaultValue(false)]
        public readonly bool $isActive = false,

        #[Cast('array')]
        public readonly array $tags = [],

        #[DefaultValue('user')]
        public readonly string $role = 'user',
    ) {}
}

class EdgeCaseUserDTO extends DataTransferObject
{
    public function __construct(
        #[Required, Email]
        public readonly string $email,

        #[Required, Min(2), Max(50)]
        public readonly string $name,

        #[Nullable]
        public readonly ?string $bio = null,

        #[Hidden]
        public readonly ?string $password = null,

        #[DefaultValue(0)]
        public readonly int $loginCount = 0,
    ) {}
}

// ── Tests ──────────────────────────────────────────────────────────

describe('DTO edge cases and immutability', function (): void {
    describe('fromArray hydration', function (): void {
        it('hydrates with only required fields', function (): void {
            $dto = EdgeCaseProfileDTO::fromArray([
                'email' => 'test@example.com',
            ], validate: false);

            expect($dto)->toBeInstanceOf(EdgeCaseProfileDTO::class);
            expect($dto->email)->toBe('test@example.com');
            expect($dto->displayName)->toBeNull();
            expect($dto->isActive)->toBeFalse();
            expect($dto->tags)->toBe([]);
            expect($dto->role)->toBe('user');
        });

        it('respects MapFrom key aliasing', function (): void {
            $dto = EdgeCaseAddressDTO::fromArray([
                'street' => '123 Main St',
                'city' => 'Springfield',
                'zip_code' => '62701',
            ], validate: false);

            expect($dto->zip)->toBe('62701');
        });

        it('uses default when mapped key is absent', function (): void {
            $dto = EdgeCaseAddressDTO::fromArray([
                'street' => '123 Main St',
                'city' => 'Springfield',
            ], validate: false);

            expect($dto->zip)->toBeNull();
        });

        it('preserves explicit null for mapped keys', function (): void {
            $dto = EdgeCaseAddressDTO::fromArray([
                'street' => '123 Main St',
                'city' => 'Springfield',
                'zip_code' => null,
            ], validate: false);

            expect($dto->zip)->toBeNull();
        });

        it('preserves explicit empty string', function (): void {
            $dto = EdgeCaseProfileDTO::fromArray([
                'email' => 'test@example.com',
                'display_name' => '',
            ], validate: false);

            expect($dto->displayName)->toBe('');
        });

        it('hydrates with Cast array from JSON string', function (): void {
            $dto = EdgeCaseProfileDTO::fromArray([
                'email' => 'test@example.com',
                'tags' => '["php","laravel"]',
            ], validate: false);

            expect($dto->tags)->toBe(['php', 'laravel']);
        });
    });

    describe('serialization', function (): void {
        it('toArray excludes Hidden properties', function (): void {
            $dto = EdgeCaseAddressDTO::fromArray([
                'street' => '123 Main St',
                'city' => 'Springfield',
                'zip_code' => '62701',
                'internalNotes' => 'secret-note',
            ], validate: false);

            $array = $dto->toArray();

            expect($array)->not->toHaveKey('internalNotes');
            expect($array)->toHaveKeys(['street', 'city', 'zip']);
        });

        it('allValues includes Hidden properties', function (): void {
            $dto = EdgeCaseAddressDTO::fromArray([
                'street' => '123 Main St',
                'city' => 'Springfield',
                'internalNotes' => 'secret-note',
            ], validate: false);

            $all = $dto->allValues();

            expect($all)->toHaveKey('internalNotes');
            expect($all['internalNotes'])->toBe('secret-note');
        });

        it('toJson produces valid JSON string', function (): void {
            $dto = EdgeCaseProfileDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Test User',
            ], validate: false);

            $json = $dto->toJson();

            expect($json)->toBeJson();
            $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
            expect($decoded['email'])->toBe('test@example.com');
        });

        it('jsonSerialize returns toArray output', function (): void {
            $dto = EdgeCaseProfileDTO::fromArray([
                'email' => 'test@example.com',
            ], validate: false);

            expect($dto->jsonSerialize())->toBe($dto->toArray());
        });
    });

    describe('selective output', function (): void {
        it('only returns specified fields', function (): void {
            $dto = EdgeCaseProfileDTO::fromArray([
                'email' => 'test@example.com',
                'display_name' => 'Test User',
                'role' => 'admin',
            ], validate: false);

            $only = $dto->only('email', 'role');

            expect($only)->toHaveCount(2);
            expect($only)->toHaveKeys(['email', 'role']);
            expect($only)->not->toHaveKey('display_name');
        });

        it('only accepts single string key', function (): void {
            $dto = EdgeCaseProfileDTO::fromArray([
                'email' => 'test@example.com',
            ], validate: false);

            $only = $dto->only('email');

            expect($only)->toHaveCount(1);
            expect($only['email'])->toBe('test@example.com');
        });

        it('except removes specified fields', function (): void {
            $dto = EdgeCaseProfileDTO::fromArray([
                'email' => 'test@example.com',
                'display_name' => 'Test User',
                'role' => 'admin',
            ], validate: false);

            $except = $dto->except('email');

            expect($except)->not->toHaveKey('email');
            expect($except)->toHaveKey('display_name');
        });

        it('only ignores non-existent keys gracefully', function (): void {
            $dto = EdgeCaseProfileDTO::fromArray([
                'email' => 'test@example.com',
            ], validate: false);

            $only = $dto->only('email', 'non_existent');

            expect($only)->toHaveCount(1);
        });
    });

    describe('immutability', function (): void {
        it('with() creates a new instance', function (): void {
            $dto = EdgeCaseUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Alice',
            ], validate: false);

            $updated = $dto->with(['name' => 'Bob']);

            expect($updated)->not->toBe($dto);
            expect($dto->name)->toBe('Alice');
            expect($updated->name)->toBe('Bob');
            expect($updated->email)->toBe('test@example.com');
        });

        it('with() preserves unchanged fields', function (): void {
            $dto = EdgeCaseUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Alice',
                'bio' => 'Hello',
                'loginCount' => 5,
            ], validate: false);

            $updated = $dto->with(['name' => 'Bob']);

            expect($updated->email)->toBe('test@example.com');
            expect($updated->bio)->toBe('Hello');
            expect($updated->loginCount)->toBe(5);
        });

        it('equals compares public serialization output', function (): void {
            $dto1 = EdgeCaseUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Alice',
            ], validate: false);

            $dto2 = EdgeCaseUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Alice',
            ], validate: false);

            $dto3 = EdgeCaseUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Bob',
            ], validate: false);

            expect($dto1->equals($dto2))->toBeTrue();
            expect($dto1->equals($dto3))->toBeFalse();
        });

        it('equals ignores hidden fields', function (): void {
            $dto1 = EdgeCaseUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Alice',
                'password' => 'secret1',
            ], validate: false);

            $dto2 = EdgeCaseUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Alice',
                'password' => 'different_secret',
            ], validate: false);

            // Password is hidden, so equals should be true
            expect($dto1->equals($dto2))->toBeTrue();
        });
    });

    describe('isEmpty / isNotEmpty', function (): void {
        it('isEmpty returns true when all properties are at default/empty', function (): void {
            $dto = EdgeCaseUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Alice',
            ], validate: false);

            // bio=null, password=null, loginCount=0 — 0 is NOT empty per contract
            expect($dto->isEmpty())->toBeFalse();
        });

        it('isEmpty returns false when non-zero int exists', function (): void {
            $dto = EdgeCaseUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Alice',
                'loginCount' => 5,
            ], validate: false);

            expect($dto->isEmpty())->toBeFalse();
        });
    });

    describe('fromJson', function (): void {
        it('creates DTO from valid JSON string', function (): void {
            $dto = EdgeCaseUserDTO::fromJson(
                json_encode(['email' => 'test@example.com', 'name' => 'Alice']),
                validate: false,
            );

            expect($dto)->toBeInstanceOf(EdgeCaseUserDTO::class);
            expect($dto->email)->toBe('test@example.com');
            expect($dto->name)->toBe('Alice');
        });

        it('throws DTOException on invalid JSON', function (): void {
            expect(function (): void {
                EdgeCaseUserDTO::fromJson('not-json', validate: false);
            })->toThrow(DTOException::class);
        });

        it('throws DTOException on JSON array (not object)', function (): void {
            expect(function (): void {
                EdgeCaseUserDTO::fromJson('[1,2,3]', validate: false);
            })->toThrow(DTOException::class);
        });

        it('JSON round-trips correctly', function (): void {
            $original = EdgeCaseUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Alice',
                'bio' => 'Developer',
                'loginCount' => 10,
            ], validate: false);

            $json = $original->toJson();
            $restored = EdgeCaseUserDTO::fromJson($json, validate: false);

            expect($restored->email)->toBe('test@example.com');
            expect($restored->name)->toBe('Alice');
            expect($restored->bio)->toBe('Developer');
            expect($restored->loginCount)->toBe(10);
            expect($original->equals($restored))->toBeTrue();
        });
    });

    describe('fromPartialArray', function (): void {
        it('hydrates partial data with defaults', function (): void {
            $dto = EdgeCaseUserDTO::fromPartialArray([
                'email' => 'test@example.com',
            ], validatePresent: false);

            expect($dto->email)->toBe('test@example.com');
            expect($dto->name)->toBe('');  // type-appropriate empty for string
        });

        it('fromPartialArray handles missing non-nullable strings', function (): void {
            $dto = EdgeCaseUserDTO::fromPartialArray([
                'email' => 'test@example.com',
                'name' => 'Bob',
            ], validatePresent: false);

            expect($dto->name)->toBe('Bob');
            expect($dto->bio)->toBeNull(); // nullable gets null
        });
    });

    describe('rules generation', function (): void {
        it('generates rules with Required and Email', function (): void {
            $rules = EdgeCaseUserDTO::rules();

            expect($rules)->toHaveKey('email');
            expect($rules['email'])->toContain('required');
            expect($rules['email'])->toContain('email');
        });

        it('generates rules with Min and Max', function (): void {
            $rules = EdgeCaseUserDTO::rules();

            expect($rules)->toHaveKey('name');
            expect($rules['name'])->toContain('required');
            expect($rules['name'])->toContain('min:2');
            expect($rules['name'])->toContain('max:50');
        });

        it('nullable properties do not have required rule', function (): void {
            $rules = EdgeCaseUserDTO::rules();

            expect($rules['bio'])->not->toContain('required');
        });

        it('rulesFor returns same as rules by default', function (): void {
            expect(EdgeCaseUserDTO::rulesFor('create'))
                ->toBe(EdgeCaseUserDTO::rules());
        });
    });

    describe('DtoCollection', function (): void {
        it('create collection with items', function (): void {
            $dtoArray = [
                EdgeCaseProfileDTO::fromArray(['email' => 'a@test.com'], validate: false),
                EdgeCaseProfileDTO::fromArray(['email' => 'b@test.com'], validate: false),
            ];

            $col = new DtoCollection($dtoArray);

            expect($col->count())->toBe(2);
            expect($col->isEmpty())->toBeFalse();
            expect($col->isNotEmpty())->toBeTrue();
        });

        it('collection toArray serializes each DTO', function (): void {
            $dtoArray = [
                EdgeCaseProfileDTO::fromArray(['email' => 'a@test.com', 'display_name' => 'A'], validate: false),
                EdgeCaseProfileDTO::fromArray(['email' => 'b@test.com', 'display_name' => 'B'], validate: false),
            ];

            $col = new DtoCollection($dtoArray);
            $arr = $col->toArray();

            expect($arr)->toHaveCount(2);
            expect($arr[0]['email'])->toBe('a@test.com');
            expect($arr[1]['email'])->toBe('b@test.com');
        });

        it('collection pluck extracts single field', function (): void {
            $dtoArray = [
                EdgeCaseProfileDTO::fromArray(['email' => 'a@test.com'], validate: false),
                EdgeCaseProfileDTO::fromArray(['email' => 'b@test.com'], validate: false),
            ];

            $col = new DtoCollection($dtoArray);
            $emails = $col->pluck('email');

            expect($emails)->toBe(['a@test.com', 'b@test.com']);
        });

        it('collection append returns new instance', function (): void {
            $dtoArray = [
                EdgeCaseProfileDTO::fromArray(['email' => 'a@test.com'], validate: false),
            ];

            $col = new DtoCollection($dtoArray);
            $newDto = EdgeCaseProfileDTO::fromArray(['email' => 'b@test.com'], validate: false);
            $appended = $col->append($newDto);

            expect($col->count())->toBe(1);    // original unchanged
            expect($appended->count())->toBe(2); // new collection
        });

        it('collection merge combines both', function (): void {
            $col1 = new DtoCollection([
                EdgeCaseProfileDTO::fromArray(['email' => 'a@test.com'], validate: false),
            ]);
            $col2 = new DtoCollection([
                EdgeCaseProfileDTO::fromArray(['email' => 'b@test.com'], validate: false),
            ]);

            $merged = $col1->merge($col2);

            expect($merged->count())->toBe(2);
            expect($col1->count())->toBe(1); // originals unchanged
            expect($col2->count())->toBe(1);
        });

        it('collection first/last return correct items', function (): void {
            $dtoArray = [
                EdgeCaseProfileDTO::fromArray(['email' => 'a@test.com'], validate: false),
                EdgeCaseProfileDTO::fromArray(['email' => 'b@test.com'], validate: false),
                EdgeCaseProfileDTO::fromArray(['email' => 'c@test.com'], validate: false),
            ];

            $col = new DtoCollection($dtoArray);

            expect($col->first()->email)->toBe('a@test.com');
            expect($col->last()->email)->toBe('c@test.com');
        });

        it('collection map returns plain array', function (): void {
            $dtoArray = [
                EdgeCaseProfileDTO::fromArray(['email' => 'a@test.com', 'display_name' => 'Alice'], validate: false),
                EdgeCaseProfileDTO::fromArray(['email' => 'b@test.com', 'display_name' => 'Bob'], validate: false),
            ];

            $col = new DtoCollection($dtoArray);
            $names = $col->map(fn (EdgeCaseProfileDTO $d): string => $d->displayName ?? '');

            expect($names)->toBe(['Alice', 'Bob']);
        });

        it('collection filter returns new collection', function (): void {
            $dtoArray = [
                EdgeCaseProfileDTO::fromArray(['email' => 'a@test.com'], validate: false),
                EdgeCaseProfileDTO::fromArray(['email' => 'b@test.com'], validate: false),
            ];

            $col = new DtoCollection($dtoArray);
            $filtered = $col->filter(fn (EdgeCaseProfileDTO $d): bool => str_starts_with($d->email, 'a'));

            expect($filtered->count())->toBe(1);
            expect($filtered->first()->email)->toBe('a@test.com');
        });

        it('collection rejects non-DTO items', function (): void {
            expect(function (): void {
                new DtoCollection(['not-a-dto']);
            })->toThrow(\InvalidArgumentException::class);
        });

        it('collection offsetSet rejects non-DTO values', function (): void {
            $col = new DtoCollection();

            expect(function () use ($col): void {
                $col[] = 'not-a-dto';
            })->toThrow(\InvalidArgumentException::class);
        });

        it('collection jsonSerialize returns toArray', function (): void {
            $dtoArray = [
                EdgeCaseProfileDTO::fromArray(['email' => 'a@test.com'], validate: false),
            ];

            $col = new DtoCollection($dtoArray);

            expect($col->jsonSerialize())->toBe($col->toArray());
        });

        it('collection pluckKey builds key-value map', function (): void {
            $dtoArray = [
                EdgeCaseProfileDTO::fromArray(['email' => 'a@test.com', 'display_name' => 'Alice'], validate: false),
                EdgeCaseProfileDTO::fromArray(['email' => 'b@test.com', 'display_name' => 'Bob'], validate: false),
            ];

            $col = new DtoCollection($dtoArray);
            $map = $col->pluckKey('email', 'displayName');

            expect($map)->toBe([
                'a@test.com' => 'Alice',
                'b@test.com' => 'Bob',
            ]);
        });
    });

    describe('validation', function (): void {
        it('validateArray returns validated data', function (): void {
            $result = EdgeCaseUserDTO::validateArray([
                'email' => 'test@example.com',
                'name' => 'Alice',
            ]);

            expect($result['email'])->toBe('test@example.com');
            expect($result['name'])->toBe('Alice');
        });

        it('validateArray throws on validation failure', function (): void {
            expect(function (): void {
                EdgeCaseUserDTO::validateArray([
                    'email' => 'not-an-email',
                    'name' => 'A', // too short (min:2)
                ]);
            })->toThrow(ValidationException::class);
        });

        it('validatePartialArray only validates present fields', function (): void {
            // Only email is present — name has default empty value in partial
            $result = EdgeCaseUserDTO::validatePartialArray([
                'email' => 'test@example.com',
            ]);

            expect($result)->toHaveKey('email');
        });
    });
});
