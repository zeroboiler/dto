<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use ZeroBoiler\DTO\Contracts\FromRequestDTO;
use ZeroBoiler\DTO\Contracts\ValidatableDTO;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;

/**
 * Issue #18: DTO::rulesFor() and contracts for ResourceController integration.
 *
 * Ensures DTOs implement ValidatableDTO and FromRequestDTO contracts,
 * providing the methods that ResourceController expects.
 */
describe('Issue #18: ValidatableDTO contract', function (): void {
    it('DataTransferObject implements ValidatableDTO', function (): void {
        expect(is_subclass_of(DataTransferObject::class, ValidatableDTO::class))->toBeTrue();
    });

    it('DataTransferObject implements FromRequestDTO', function (): void {
        expect(is_subclass_of(DataTransferObject::class, FromRequestDTO::class))->toBeTrue();
    });

    it('DTO subclasses implement both contracts', function (): void {
        expect(is_subclass_of(CreateUserDTO::class, ValidatableDTO::class))->toBeTrue();
        expect(is_subclass_of(CreateUserDTO::class, FromRequestDTO::class))->toBeTrue();
    });

    it('rules() returns the attribute-derived rules', function (): void {
        $rules = CreateUserDTO::rules();

        expect($rules)->toHaveKey('email');
        expect($rules)->toHaveKey('name');
    });

    it('rulesFor("create") returns rules by default', function (): void {
        $rules = CreateUserDTO::rulesFor('create');

        expect($rules)->toHaveKey('email');
        expect($rules)->toHaveKey('name');
    });

    it('rulesFor("update") returns same rules by default', function (): void {
        $createRules = CreateUserDTO::rulesFor('create');
        $updateRules = CreateUserDTO::rulesFor('update');

        expect($createRules)->toBe($updateRules);
    });

    it('rulesFor() returns same as rules() by default', function (): void {
        $rules = CreateUserDTO::rules();
        $rulesFor = CreateUserDTO::rulesFor('any_action');

        expect($rulesFor)->toBe($rules);
    });
});

describe('Issue #18: FromRequestDTO contract', function (): void {
    it('fromRequest() creates DTO from request data', function (): void {
        $request = Request::create('/users', 'POST', [
            'email' => 'test@example.com',
            'name' => 'Test User',
        ]);

        $dto = CreateUserDTO::fromRequest($request);

        expect($dto->email)->toBe('test@example.com')
            ->and($dto->name)->toBe('Test User');
    });

    it('fromRequest() validates by default', function (): void {
        $request = Request::create('/users', 'POST', [
            'email' => 'invalid',
            'name' => 'Test',
        ]);

        expect(fn (): CreateUserDTO => CreateUserDTO::fromRequest($request))
            ->toThrow(ValidationException::class);
    });
});

describe('Issue #18: rulesFor() override capability', function (): void {
    it('subclasses can override rulesFor() for action-specific rules', function (): void {
        $dtoClass = new class extends DataTransferObject
        {
            public function __construct(
                public string $name = '',
                public ?string $email = null,
            ) {}

            public static function rulesFor(string $action): array
            {
                if ($action === 'update') {
                    return ['name' => ['sometimes']];
                }

                return ['name' => ['required'], 'email' => ['sometimes']];
            }
        };

        $createRules = $dtoClass::rulesFor('create');
        expect($createRules)->toHaveKey('name');
        expect($createRules['name'])->toContain('required');

        $updateRules = $dtoClass::rulesFor('update');
        expect($updateRules)->toBe(['name' => ['sometimes']]);
    });
});

describe('Issue #18: Contract method signatures', function (): void {
    it('ValidatableDTO requires rules() method', function (): void {
        $reflection = new ReflectionClass(ValidatableDTO::class);

        expect($reflection->hasMethod('rules'))->toBeTrue();
        expect($reflection->hasMethod('rulesFor'))->toBeTrue();
    });

    it('FromRequestDTO requires fromRequest() method', function (): void {
        $reflection = new ReflectionClass(FromRequestDTO::class);

        expect($reflection->hasMethod('fromRequest'))->toBeTrue();
    });

    it('rulesFor() accepts a string action parameter', function (): void {
        $reflection = new ReflectionClass(ValidatableDTO::class);
        $method = $reflection->getMethod('rulesFor');
        $params = $method->getParameters();

        expect($params)->toHaveCount(1);
        expect($params[0]->getName())->toBe('action');
        expect($params[0]->getType()->getName())->toBe('string');
    });
});
