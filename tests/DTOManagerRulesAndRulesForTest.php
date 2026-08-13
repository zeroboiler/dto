<?php

declare(strict_types=1);

use ZeroBoiler\DTO\DTOManager;
use ZeroBoiler\DTO\Facades\DTO;
use ZeroBoiler\DTO\Tests\Fixtures\ActionScopedDTO;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\MinimalDTO;
use ZeroBoiler\DTO\Tests\Fixtures\ScalarConstraintsDTO;
use ZeroBoiler\DTO\Tests\Fixtures\StrictValidationDTO;

describe('DTOManager rules and rulesFor', function () {
    it('rules returns array of validation rules', function () {
        $manager = new DTOManager;
        $rules = $manager->rules(CreateUserDTO::class);

        expect($rules)->toBeArray();
    });

    it('rules returns string-keyed array with array values', function () {
        $manager = new DTOManager;
        $rules = $manager->rules(CreateUserDTO::class);

        foreach ($rules as $key => $rule) {
            expect($key)->toBeString();
            expect($rule)->toBeArray();
        }
    });

    it('rules includes required attribute as a rule', function () {
        $manager = new DTOManager;
        $rules = $manager->rules(StrictValidationDTO::class);

        expect($rules)->toHaveKey('name');
        expect($rules['name'])->toContain('required');
    });

    it('rules includes min constraint when present', function () {
        $manager = new DTOManager;
        $rules = $manager->rules(ScalarConstraintsDTO::class);

        expect($rules)->toHaveKey('age');
    });

    it('rulesFor returns array with all string keys', function () {
        $manager = new DTOManager;
        $rules = $manager->rulesFor(ActionScopedDTO::class, 'create');

        expect($rules)->toBeArray();
        foreach ($rules as $key => $rule) {
            expect($key)->toBeString();
            expect($rule)->toBeArray();
        }
    });

    it('rulesFor create returns required rules', function () {
        $manager = new DTOManager;
        $rules = $manager->rulesFor(ActionScopedDTO::class, 'create');

        expect($rules['email'])->toContain('required');
        expect($rules['password'])->toContain('required');
    });

    it('rulesFor update returns sometimes instead of required', function () {
        $manager = new DTOManager;
        $rules = $manager->rulesFor(ActionScopedDTO::class, 'update');

        expect($rules['email'])->toContain('sometimes');
        expect($rules['email'])->not->toContain('required');
        expect($rules['password'])->toContain('sometimes');
        expect($rules['password'])->not->toContain('required');
    });

    it('rulesFor update preserves email validation', function () {
        $manager = new DTOManager;
        $rules = $manager->rulesFor(ActionScopedDTO::class, 'update');

        expect($rules['email'])->toContain('email');
    });

    it('rulesFor update preserves min constraint on password', function () {
        $manager = new DTOManager;
        $rules = $manager->rulesFor(ActionScopedDTO::class, 'update');

        expect($rules['password'])->toContain('min:8');
    });

    it('rulesFor unknown action returns base rules', function () {
        $manager = new DTOManager;
        $base = $manager->rules(ActionScopedDTO::class);
        $unknown = $manager->rulesFor(ActionScopedDTO::class, 'nonexistent_action');

        expect($unknown)->toBe($base);
    });

    it('rules and rulesFor are consistent for base rules', function () {
        $manager = new DTOManager;
        $base = $manager->rules(ActionScopedDTO::class);
        $forCreate = $manager->rulesFor(ActionScopedDTO::class, 'create');

        expect($base)->toBe($forCreate);
    });

    it('rules returns empty array for DTO with no constraints', function () {
        $manager = new DTOManager;
        $rules = $manager->rules(MinimalDTO::class);

        expect($rules)->toBeArray();
    });

    it('makeFromJson creates DTO from valid JSON', function () {
        $manager = new DTOManager;
        $json = json_encode(['email' => 'test@example.com', 'password' => 'securepass123']);

        $dto = $manager->makeFromJson(ActionScopedDTO::class, $json);

        expect($dto)->toBeInstanceOf(ActionScopedDTO::class);
        expect($dto->email)->toBe('test@example.com');
        expect($dto->password)->toBe('securepass123');
    });

    it('makeFromJson throws DTOException for invalid JSON', function () {
        $manager = new DTOManager;

        expect(fn () => $manager->makeFromJson(CreateUserDTO::class, 'not-json'))
            ->toThrow(\ZeroBoiler\DTO\Exceptions\DTOException::class);
    });

    it('validate returns validated data array', function () {
        $manager = new DTOManager;
        $data = $manager->validate(ActionScopedDTO::class, [
            'email' => 'test@example.com',
            'password' => 'securepass123',
        ]);

        expect($data)->toBeArray();
        expect($data['email'])->toBe('test@example.com');
    });
});
