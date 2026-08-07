<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use Illuminate\Validation\ValidationException;
use ZeroBoiler\DTO\Attributes\DefaultValue;
use ZeroBoiler\DTO\Attributes\Email;
use ZeroBoiler\DTO\Attributes\Hidden;
use ZeroBoiler\DTO\Attributes\Max;
use ZeroBoiler\DTO\Attributes\Min;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;

// ── Test Fixtures ──────────────────────────────────────────────────────────

class RoundtripUserDTO extends DataTransferObject
{
    public function __construct(
        #[Required, Email]
        public readonly string $email,

        #[Required, Min(2), Max(50)]
        public readonly string $name,

        #[DefaultValue('active')]
        public readonly string $status = 'active',

        #[Hidden]
        public readonly ?string $password = null,

        #[DefaultValue([])]
        public readonly array $tags = [],
    ) {}
}

class RoundtripNestedDTO extends DataTransferObject
{
    public function __construct(
        #[Required]
        public readonly string $title,

        public readonly ?RoundtripUserDTO $author = null,
    ) {}
}

class RoundtripEmptyDTO extends DataTransferObject
{
    public function __construct(
        public readonly ?string $name = null,

        public readonly int $count = 0,
    ) {}
}

// ── Tests ─────────────────────────────────────────────────────────────────

describe('DTO serialization roundtrip', function (): void {
    describe('toArray()', function (): void {
        it('excludes hidden fields', function (): void {
            $dto = RoundtripUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Alice',
                'password' => 'secret123',
            ], validate: false);

            expect($dto->toArray())->not->toHaveKey('password');
            expect($dto->allValues())->toHaveKey('password');
            expect($dto->allValues()['password'])->toBe('secret123');
        });

        it('includes default values for missing optional fields', function (): void {
            $dto = RoundtripUserDTO::fromArray([
                'email' => 'test@example.com',
                'name' => 'Bob',
            ], validate: false);

            expect($dto->toArray())->toHaveKey('status');
            expect($dto->toArray()['status'])->toBe('active');
            expect($dto->toArray())->toHaveKey('tags');
            expect($dto->toArray()['tags'])->toBe([]);
        });

        it('preserves all provided values including empty strings and zeros', function (): void {
            $dto = RoundtripEmptyDTO::fromArray([
                'name' => '',
                'count' => 0,
            ], validate: false);

            expect($dto->name)->toBe('');
            expect($dto->count)->toBe(0);
            expect($dto->isEmpty())->toBeTrue();
        });
    });

    describe('toJson()', function (): void {
        it('produces valid JSON', function (): void {
            $dto = RoundtripUserDTO::fromArray([
                'email' => 'json@test.com',
                'name' => 'Json',
            ], validate: false);

            $json = $dto->toJson();

            expect($json)->toBeJson();
            $decoded = json_decode($json, true);
            expect($decoded)->toBeArray();
            expect($decoded['email'])->toBe('json@test.com');
            expect($decoded['name'])->toBe('Json');
            expect($decoded)->not->toHaveKey('password');
        });

        it('respects JSON encoding options', function (): void {
            $dto = RoundtripUserDTO::fromArray([
                'email' => 'pretty@test.com',
                'name' => 'Pretty',
            ], validate: false);

            $json = $dto->toJson(JSON_PRETTY_PRINT);

            expect($json)->toContain("\n");
        });
    });

    describe('jsonSerialize()', function (): void {
        it('returns the same array as toArray()', function (): void {
            $dto = RoundtripUserDTO::fromArray([
                'email' => 'serialize@test.com',
                'name' => 'Serialize',
            ], validate: false);

            expect($dto->jsonSerialize())->toBe($dto->toArray());
        });
    });

    describe('equals()', function (): void {
        it('returns true for DTOs with identical values', function (): void {
            $a = RoundtripUserDTO::fromArray([
                'email' => 'same@test.com',
                'name' => 'Same',
            ], validate: false);
            $b = RoundtripUserDTO::fromArray([
                'email' => 'same@test.com',
                'name' => 'Same',
            ], validate: false);

            expect($a->equals($b))->toBeTrue();
        });

        it('returns false for DTOs with different values', function (): void {
            $a = RoundtripUserDTO::fromArray([
                'email' => 'a@test.com',
                'name' => 'A',
            ], validate: false);
            $b = RoundtripUserDTO::fromArray([
                'email' => 'b@test.com',
                'name' => 'B',
            ], validate: false);

            expect($a->equals($b))->toBeFalse();
        });

        it('ignores hidden fields in comparison', function (): void {
            $a = RoundtripUserDTO::fromArray([
                'email' => 'hidden@test.com',
                'name' => 'Hidden',
                'password' => 'pass1',
            ], validate: false);
            $b = RoundtripUserDTO::fromArray([
                'email' => 'hidden@test.com',
                'name' => 'Hidden',
                'password' => 'pass2',
            ], validate: false);

            // Both have same toArray() output since password is hidden
            expect($a->equals($b))->toBeTrue();
        });
    });

    describe('isEmpty() / isNotEmpty()', function (): void {
        it('returns true when all properties are empty defaults', function (): void {
            $dto = RoundtripEmptyDTO::fromArray([], validate: false);

            expect($dto->isEmpty())->toBeTrue();
            expect($dto->isNotEmpty())->toBeFalse();
        });

        it('returns false when any property has a non-empty value', function (): void {
            $dto = RoundtripEmptyDTO::fromArray(['name' => 'Alice'], validate: false);

            expect($dto->isEmpty())->toBeFalse();
            expect($dto->isNotEmpty())->toBeTrue();
        });
    });

    describe('only() / except()', function (): void {
        it('only() returns specified fields', function (): void {
            $dto = RoundtripUserDTO::fromArray([
                'email' => 'only@test.com',
                'name' => 'Only',
                'status' => 'active',
            ], validate: false);

            $result = $dto->only('email', 'name');

            expect($result)->toHaveKeys(['email', 'name']);
            expect($result)->not->toHaveKey('status');
            expect($result)->not->toHaveKey('password');
        });

        it('only() accepts a single string key', function (): void {
            $dto = RoundtripUserDTO::fromArray([
                'email' => 'single@test.com',
                'name' => 'Single',
            ], validate: false);

            $result = $dto->only('email');

            expect($result)->toHaveCount(1);
            expect($result['email'])->toBe('single@test.com');
        });

        it('except() excludes specified fields', function (): void {
            $dto = RoundtripUserDTO::fromArray([
                'email' => 'except@test.com',
                'name' => 'Except',
                'status' => 'active',
            ], validate: false);

            $result = $dto->except('status');

            expect($result)->toHaveKey('email');
            expect($result)->toHaveKey('name');
            expect($result)->not->toHaveKey('status');
        });
    });

    describe('with() immutable update', function (): void {
        it('creates a new DTO with overrides', function (): void {
            $original = RoundtripUserDTO::fromArray([
                'email' => 'with@test.com',
                'name' => 'Original',
            ], validate: false);

            $updated = $original->with(['name' => 'Updated']);

            expect($original->name)->toBe('Original');
            expect($updated->name)->toBe('Updated');
            expect($updated->email)->toBe('with@test.com');
        });

        it('always validates the merged data', function (): void {
            $dto = RoundtripUserDTO::fromArray([
                'email' => 'valid@test.com',
                'name' => 'Valid Name',
            ], validate: true);

            // Passing invalid data to with() should throw
            expect(fn (): mixed => $dto->with(['email' => 'not-an-email']))
                ->toThrow(ValidationException::class);
        });
    });

    describe('fromJson()', function (): void {
        it('hydrates from a valid JSON string', function (): void {
            $json = '{"email":"json@test.com","name":"FromJson"}';
            $dto = RoundtripUserDTO::fromJson($json, validate: false);

            expect($dto->email)->toBe('json@test.com');
            expect($dto->name)->toBe('FromJson');
        });

        it('throws on invalid JSON', function (): void {
            expect(fn (): mixed => RoundtripUserDTO::fromJson('{invalid}', validate: false))
                ->toThrow(\ZeroBoiler\DTO\Exceptions\DTOException::class);
        });
    });

    describe('nested DTO roundtrip', function (): void {
        it('serializes nested DTOs recursively', function (): void {
            $dto = RoundtripNestedDTO::fromArray([
                'title' => 'Test Article',
                'author' => [
                    'email' => 'author@test.com',
                    'name' => 'Author',
                ],
            ], validate: false);

            $array = $dto->toArray();

            expect($array['title'])->toBe('Test Article');
            expect($array['author'])->toBeArray();
            expect($array['author']['email'])->toBe('author@test.com');
            expect($array['author']['name'])->toBe('Author');
        });

        it('handles null nested DTOs', function (): void {
            $dto = RoundtripNestedDTO::fromArray([
                'title' => 'No Author',
            ], validate: false);

            expect($dto->author)->toBeNull();
            expect($dto->toArray()['author'])->toBeNull();
        });
    });

    describe('DtoCollection serialization', function (): void {
        it('toArray serializes all DTOs', function (): void {
            $collection = DtoCollection::make([
                RoundtripUserDTO::fromArray(['email' => 'col1@test.com', 'name' => 'One'], validate: false),
                RoundtripUserDTO::fromArray(['email' => 'col2@test.com', 'name' => 'Two'], validate: false),
            ]);

            $array = $collection->toArray();

            expect($array)->toHaveCount(2);
            expect($array[0]['email'])->toBe('col1@test.com');
            expect($array[1]['email'])->toBe('col2@test.com');
            // Hidden fields should be excluded
            expect($array[0])->not->toHaveKey('password');
        });

        it('jsonSerialize produces valid JSON', function (): void {
            $collection = DtoCollection::make([
                RoundtripUserDTO::fromArray(['email' => 'js@test.com', 'name' => 'JS'], validate: false),
            ]);

            $json = json_encode($collection);

            expect($json)->toBeJson();
        });

        it('push returns the same collection (fluent)', function (): void {
            $collection = DtoCollection::make([
                RoundtripUserDTO::fromArray(['email' => 'push@test.com', 'name' => 'Push'], validate: false),
            ]);

            $dto = RoundtripUserDTO::fromArray(['email' => 'new@test.com', 'name' => 'New'], validate: false);
            $result = $collection->push($dto);

            expect($result)->toBe($collection);
            expect($collection->count())->toBe(2);
        });

        it('filter returns a new collection', function (): void {
            $collection = DtoCollection::make([
                RoundtripUserDTO::fromArray(['email' => 'a@test.com', 'name' => 'A', 'status' => 'active'], validate: false),
                RoundtripUserDTO::fromArray(['email' => 'b@test.com', 'name' => 'B', 'status' => 'inactive'], validate: false),
            ]);

            $active = $collection->filter(fn (DataTransferObject $dto): bool => $dto->toArray()['status'] === 'active');

            expect($active)->toBeInstanceOf(DtoCollection::class);
            expect($active->count())->toBe(1);
        });

        it('map returns plain array', function (): void {
            $collection = DtoCollection::make([
                RoundtripUserDTO::fromArray(['email' => 'map@test.com', 'name' => 'Map'], validate: false),
            ]);

            $names = $collection->map(fn (DataTransferObject $dto): string => $dto->toArray()['name']);

            expect($names)->toBe(['Map']);
        });
    });

    describe('rules() and rulesFor()', function (): void {
        it('returns validation rules', function (): void {
            $rules = RoundtripUserDTO::rules();

            expect($rules)->toBeArray();
            expect($rules)->toHaveKey('email');
            expect($rules)->toHaveKey('name');
        });

        it('rulesFor returns same rules by default', function (): void {
            expect(RoundtripUserDTO::rulesFor('create'))
                ->toBe(RoundtripUserDTO::rules());
            expect(RoundtripUserDTO::rulesFor('update'))
                ->toBe(RoundtripUserDTO::rules());
        });
    });

    describe('allValues() vs toArray()', function (): void {
        it('allValues includes hidden fields', function (): void {
            $dto = RoundtripUserDTO::fromArray([
                'email' => 'av@test.com',
                'name' => 'AV',
                'password' => 'hunter2',
            ], validate: false);

            expect($dto->toArray())->not->toHaveKey('password');
            expect($dto->allValues())->toHaveKey('password');
            expect($dto->allValues()['password'])->toBe('hunter2');
        });
    });
});
