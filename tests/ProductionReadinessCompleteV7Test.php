<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use Illuminate\Validation\ValidationException;
use ZeroBoiler\DTO\Attributes\Accepted;
use ZeroBoiler\DTO\Attributes\ArrayRule;
use ZeroBoiler\DTO\Attributes\Between;
use ZeroBoiler\DTO\Attributes\Boolean;
use ZeroBoiler\DTO\Attributes\Cast;
use ZeroBoiler\DTO\Attributes\Confirmed;
use ZeroBoiler\DTO\Attributes\Date;
use ZeroBoiler\DTO\Attributes\DefaultValue;
use ZeroBoiler\DTO\Attributes\Email;
use ZeroBoiler\DTO\Attributes\Hidden;
use ZeroBoiler\DTO\Attributes\In;
use ZeroBoiler\DTO\Attributes\Integer;
use ZeroBoiler\DTO\Attributes\MapFrom;
use ZeroBoiler\DTO\Attributes\Max;
use ZeroBoiler\DTO\Attributes\Min;
use ZeroBoiler\DTO\Attributes\Nullable;
use ZeroBoiler\DTO\Attributes\Pattern;
use ZeroBoiler\DTO\Attributes\Present;
use ZeroBoiler\DTO\Attributes\Prohibited;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\Attributes\RequiredIf;
use ZeroBoiler\DTO\Attributes\Same;
use ZeroBoiler\DTO\Attributes\Size;
use ZeroBoiler\DTO\Attributes\StartsWith;
use ZeroBoiler\DTO\Attributes\Sometimes;
use ZeroBoiler\DTO\Attributes\Url;
use ZeroBoiler\DTO\Attributes\Uuid;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Tests\Fixtures\MinimalDTO;

/**
 * Fixture: DTO with all common attribute combinations.
 */
final class ProductionReadyDTO
{
    use \ZeroBoiler\DTO\DataTransferObject;

    public function __construct(
        #[Required]
        #[Email]
        #[Max(255)]
        public readonly string $email,

        #[Required]
        #[Integer]
        #[Between(1, 120)]
        public readonly int $age,

        #[Nullable]
        #[MapFrom('display_name')]
        #[Max(100)]
        public readonly ?string $name = null,

        #[Boolean]
        #[DefaultValue(false)]
        public readonly bool $active = false,

        #[Hidden]
        public readonly string $secret = '',

        #[ArrayRule(min: 1, max: 10)]
        public readonly array $tags = [],

        #[Sometimes]
        #[Url]
        public readonly ?string $website = null,

        #[Nullable]
        #[Pattern('/^[A-Z]{2,3}$/')]
        public readonly ?string $countryCode = null,

        #[Cast('integer')]
        public readonly int $score = 0,
    ) {}
}

/**
 * Fixture: DTO with RequiredIf conditional validation.
 */
final class ConditionalValidationDTO
{
    use \ZeroBoiler\DTO\DataTransferObject;

    public function __construct(
        #[Required]
        public readonly string $type,

        #[RequiredIf(field: 'type', value: 'card')]
        public readonly ?string $cardNumber = null,

        #[RequiredIf(field: 'type', value: 'bank')]
        public readonly ?string $bankAccount = null,
    ) {}
}

/**
 * Fixture: DTO for isEmpty/isNotEmpty testing.
 */
final class EmptyStateDTO
{
    use \ZeroBoiler\DTO\DataTransferObject;

    public function __construct(
        #[DefaultValue(null)]
        public readonly ?string $name = null,

        #[DefaultValue([])]
        public readonly array $tags = [],

        #[DefaultValue(false)]
        public readonly bool $active = false,

        #[DefaultValue('')]
        public readonly string $role = '',
    ) {}
}

describe('Production Readiness — Full Attribute Hydration', function () {
    it('hydrates from array with all fields', function () {
        $dto = ProductionReadyDTO::fromArray([
            'email' => 'test@example.com',
            'age' => 30,
            'display_name' => 'John',
            'tags' => ['php', 'laravel'],
        ]);

        expect($dto->email)->toBe('test@example.com');
        expect($dto->age)->toBe(30);
        expect($dto->name)->toBe('John');
        expect($dto->tags)->toBe(['php', 'laravel']);
    });

    it('applies defaults for missing fields', function () {
        $dto = ProductionReadyDTO::fromArray([
            'email' => 'test@example.com',
            'age' => 25,
        ]);

        expect($dto->active)->toBeFalse();
        expect($dto->tags)->toBe([]);
        expect($dto->score)->toBe(0);
        expect($dto->name)->toBeNull();
        expect($dto->website)->toBeNull();
    });

    it('throws validation for missing required field', function () {
        expect(fn () => ProductionReadyDTO::fromArray([
            'age' => 25,
        ]))->toThrow(ValidationException::class);
    });

    it('throws validation for invalid email', function () {
        expect(fn () => ProductionReadyDTO::fromArray([
            'email' => 'not-an-email',
            'age' => 25,
        ]))->toThrow(ValidationException::class);
    });

    it('throws validation for out-of-range integer', function () {
        expect(fn () => ProductionReadyDTO::fromArray([
            'email' => 'test@example.com',
            'age' => 200,
        ]))->toThrow(ValidationException::class);
    });

    it('serializes toArray excluding hidden fields', function () {
        $dto = ProductionReadyDTO::fromArray([
            'email' => 'test@example.com',
            'age' => 30,
            'secret' => 'password123',
        ]);

        $arr = $dto->toArray();

        expect($arr)->toHaveKey('email');
        expect($arr)->not->toHaveKey('secret');
    });

    it('allValues includes hidden fields', function () {
        $dto = ProductionReadyDTO::fromArray([
            'email' => 'test@example.com',
            'age' => 30,
            'secret' => 'password123',
        ]);

        $all = $dto->allValues();

        expect($all)->toHaveKey('secret');
        expect($all['secret'])->toBe('password123');
    });

    it('MapFrom remaps source key correctly', function () {
        $dto = ProductionReadyDTO::fromArray([
            'email' => 'test@example.com',
            'age' => 30,
            'display_name' => 'Mapped Name',
        ]);

        expect($dto->name)->toBe('Mapped Name');
    });

    it('Cast attribute converts value type', function () {
        $dto = ProductionReadyDTO::fromArray([
            'email' => 'test@example.com',
            'age' => 30,
            'score' => '42',
        ]);

        expect($dto->score)->toBe(42);
        expect($dto->score)->toBeInt();
    });
});

describe('Production Readiness — Conditional Validation', function () {
    it('validates required_if when condition is met', function () {
        $dto = ConditionalValidationDTO::fromArray([
            'type' => 'card',
            'cardNumber' => '4111111111111111',
        ]);

        expect($dto->type)->toBe('card');
        expect($dto->cardNumber)->toBe('4111111111111111');
    });

    it('throws when required_if condition is met but field is missing', function () {
        expect(fn () => ConditionalValidationDTO::fromArray([
            'type' => 'card',
        ]))->toThrow(ValidationException::class);
    });

    it('allows optional field when condition is not met', function () {
        $dto = ConditionalValidationDTO::fromArray([
            'type' => 'cash',
        ]);

        expect($dto->type)->toBe('cash');
        expect($dto->cardNumber)->toBeNull();
        expect($dto->bankAccount)->toBeNull();
    });
});

describe('Production Readiness — isEmpty/isNotEmpty', function () {
    it('isEmpty returns true when all defaults', function () {
        $dto = EmptyStateDTO::fromArray([]);

        expect($dto->isEmpty())->toBeTrue();
    });

    it('isNotEmpty returns true when any field has value', function () {
        $dto = EmptyStateDTO::fromArray([
            'name' => 'Alice',
        ]);

        expect($dto->isNotEmpty())->toBeTrue();
        expect($dto->isEmpty())->toBeFalse();
    });
});

describe('Production Readiness — equals() and with()', function () {
    it('equals compares toArray output', function () {
        $dto1 = ProductionReadyDTO::fromArray([
            'email' => 'test@example.com',
            'age' => 30,
        ]);
        $dto2 = ProductionReadyDTO::fromArray([
            'email' => 'test@example.com',
            'age' => 30,
        ]);

        expect($dto1->equals($dto2))->toBeTrue();
    });

    it('equals returns false for different data', function () {
        $dto1 = ProductionReadyDTO::fromArray([
            'email' => 'a@example.com',
            'age' => 30,
        ]);
        $dto2 = ProductionReadyDTO::fromArray([
            'email' => 'b@example.com',
            'age' => 30,
        ]);

        expect($dto1->equals($dto2))->toBeFalse();
    });

    it('with() creates immutable copy', function () {
        $original = ProductionReadyDTO::fromArray([
            'email' => 'test@example.com',
            'age' => 30,
        ]);

        $updated = $original->with(['age' => 31]);

        expect($original->age)->toBe(30);
        expect($updated->age)->toBe(31);
        expect($updated->email)->toBe('test@example.com');
    });
});

describe('Production Readiness — only/except', function () {
    it('only returns specified fields', function () {
        $dto = ProductionReadyDTO::fromArray([
            'email' => 'test@example.com',
            'age' => 30,
            'display_name' => 'John',
        ]);

        $only = $dto->only(['email', 'age']);

        expect($only)->toHaveCount(2);
        expect($only)->toHaveKey('email');
        expect($only)->toHaveKey('age');
        expect($only)->not->toHaveKey('name');
    });

    it('except excludes specified fields', function () {
        $dto = ProductionReadyDTO::fromArray([
            'email' => 'test@example.com',
            'age' => 30,
        ]);

        $except = $dto->except(['email']);

        expect($except)->not->toHaveKey('email');
        expect($except)->toHaveKey('age');
    });
});

describe('Production Readiness — rules() and rulesFor()', function () {
    it('rules returns all validation rules', function () {
        $rules = ProductionReadyDTO::rules();

        expect($rules)->toHaveKey('email');
        expect($rules)->toHaveKey('age');
        expect($rules)->not->toHaveKey('secret');
    });

    it('rulesFor returns same rules by default', function () {
        expect(ProductionReadyDTO::rulesFor('create'))->toBe(ProductionReadyDTO::rules());
        expect(ProductionReadyDTO::rulesFor('update'))->toBe(ProductionReadyDTO::rules());
    });
});

describe('Production Readiness — DTOException', function () {
    it('invalidCast creates formatted message', function () {
        $e = DTOException::invalidCast('age', 'integer', 'string');

        expect($e->getMessage())->toContain('age');
        expect($e->getMessage())->toContain('integer');
    });

    it('invalidJson creates formatted message', function () {
        $e = DTOException::invalidJson('payload', 'syntax error');

        expect($e->getMessage())->toContain('payload');
        expect($e->getMessage())->toContain('syntax error');
    });

    it('__toString returns class name and message', function () {
        $e = DTOException::invalidCast('field', 'int', 'string');

        expect((string) $e)->toContain('DTOException');
        expect((string) $e)->toContain('field');
    });
});

describe('Production Readiness — DtoCollection Operations', function () {
    it('creates collection from DTOs', function () {
        $dto1 = ProductionReadyDTO::fromArray(['email' => 'a@test.com', 'age' => 25]);
        $dto2 = ProductionReadyDTO::fromArray(['email' => 'b@test.com', 'age' => 30]);

        $collection = new DtoCollection([$dto1, $dto2]);

        expect($collection->count())->toBe(2);
        expect($collection->isEmpty())->toBeFalse();
        expect($collection->first())->toBe($dto1);
        expect($collection->last())->toBe($dto2);
    });

    it('map returns plain array of results', function () {
        $dto1 = ProductionReadyDTO::fromArray(['email' => 'a@test.com', 'age' => 25]);
        $dto2 = ProductionReadyDTO::fromArray(['email' => 'b@test.com', 'age' => 30]);

        $collection = new DtoCollection([$dto1, $dto2]);
        $emails = $collection->map(fn ($dto) => $dto->email);

        expect($emails)->toBe(['a@test.com', 'b@test.com']);
    });

    it('filter returns new collection', function () {
        $dto1 = ProductionReadyDTO::fromArray(['email' => 'a@test.com', 'age' => 25]);
        $dto2 = ProductionReadyDTO::fromArray(['email' => 'b@test.com', 'age' => 35]);

        $collection = new DtoCollection([$dto1, $dto2]);
        $filtered = $collection->filter(fn ($dto) => $dto->age >= 30);

        expect($filtered->count())->toBe(1);
        expect($filtered->first()->email)->toBe('b@test.com');
    });

    it('append returns new collection with added item', function () {
        $dto1 = ProductionReadyDTO::fromArray(['email' => 'a@test.com', 'age' => 25]);
        $dto2 = ProductionReadyDTO::fromArray(['email' => 'b@test.com', 'age' => 30]);

        $collection = new DtoCollection([$dto1]);
        $appended = $collection->append($dto2);

        expect($collection->count())->toBe(1);
        expect($appended->count())->toBe(2);
    });

    it('merge combines two collections', function () {
        $dto1 = ProductionReadyDTO::fromArray(['email' => 'a@test.com', 'age' => 25]);
        $dto2 = ProductionReadyDTO::fromArray(['email' => 'b@test.com', 'age' => 30]);

        $c1 = new DtoCollection([$dto1]);
        $c2 = new DtoCollection([$dto2]);
        $merged = $c1->merge($c2);

        expect($merged->count())->toBe(2);
    });

    it('toArray serializes all DTOs', function () {
        $dto1 = ProductionReadyDTO::fromArray(['email' => 'a@test.com', 'age' => 25]);
        $dto2 = ProductionReadyDTO::fromArray(['email' => 'b@test.com', 'age' => 30]);

        $collection = new DtoCollection([$dto1, $dto2]);
        $arr = $collection->toArray();

        expect($arr)->toBeArray();
        expect($arr)->toHaveCount(2);
        expect($arr[0])->toHaveKey('email');
        expect($arr[0])->not->toHaveKey('secret');
    });

    it('offsetExists, offsetGet, offsetUnset work', function () {
        $dto1 = ProductionReadyDTO::fromArray(['email' => 'a@test.com', 'age' => 25]);
        $collection = new DtoCollection([$dto1]);

        expect(isset($collection[0]))->toBeTrue();
        expect($collection[0])->toBe($dto1);
        expect($collection[99])->toBeNull();

        unset($collection[0]);
        expect($collection->isEmpty())->toBeTrue();
    });

    it('clone throws RuntimeException', function () {
        $dto1 = ProductionReadyDTO::fromArray(['email' => 'a@test.com', 'age' => 25]);
        $collection = new DtoCollection([$dto1]);

        expect(fn () => clone $collection)->toThrow(\RuntimeException::class);
    });

    it('push mutates and returns same instance', function () {
        $dto1 = ProductionReadyDTO::fromArray(['email' => 'a@test.com', 'age' => 25]);
        $dto2 = ProductionReadyDTO::fromArray(['email' => 'b@test.com', 'age' => 30]);

        $collection = new DtoCollection;
        $returned = $collection->push($dto1)->push($dto2);

        expect($returned)->toBe($collection);
        expect($collection->count())->toBe(2);
    });
});

describe('Production Readiness — fromPartialArray', function () {
    it('hydrates only present fields with defaults for rest', function () {
        $dto = ProductionReadyDTO::fromPartialArray([
            'email' => 'partial@test.com',
        ]);

        expect($dto->email)->toBe('partial@test.com');
        expect($dto->active)->toBeFalse();
        expect($dto->score)->toBe(0);
    });

    it('fromPartialArray with validatePresent validates only present fields', function () {
        expect(fn () => ProductionReadyDTO::fromPartialArray([
            'email' => 'invalid-email',
        ], validatePresent: true))->toThrow(ValidationException::class);
    });

    it('fromPartialArray with validatePresent false skips validation', function () {
        $dto = ProductionReadyDTO::fromPartialArray([
            'email' => 'not-validated',
        ], validatePresent: false);

        expect($dto->email)->toBe('not-validated');
    });
});

describe('Production Readiness — toJson', function () {
    it('serializes to JSON string', function () {
        $dto = ProductionReadyDTO::fromArray([
            'email' => 'test@example.com',
            'age' => 30,
        ]);

        $json = $dto->toJson();

        expect($json)->toBeJson();
        $decoded = json_decode($json, true);

        expect($decoded['email'])->toBe('test@example.com');
        expect($decoded['age'])->toBe(30);
        expect($decoded)->not->toHaveKey('secret');
    });
});

describe('Production Readiness — Metadata Cache', function () {
    afterEach(function () {
        ProductionReadyDTO::flushMetadataCache();
    });

    it('flushMetadataCache clears all entries', function () {
        // Resolve metadata by calling fromArray
        ProductionReadyDTO::fromArray([
            'email' => 'test@example.com',
            'age' => 30,
        ], validate: false);

        ProductionReadyDTO::flushMetadataCache();

        // After flush, re-resolving should work
        $dto = ProductionReadyDTO::fromArray([
            'email' => 'test2@example.com',
            'age' => 25,
        ], validate: false);

        expect($dto->email)->toBe('test2@example.com');
    });
});
