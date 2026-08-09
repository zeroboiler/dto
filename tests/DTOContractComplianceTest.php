<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\Contracts\FromRequestDTO;
use ZeroBoiler\DTO\Contracts\ValidatableDTO;
use ZeroBoiler\DTO\Contracts\ValidationAttribute;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Tests\Fixtures\MinimalDTO;
use ZeroBoiler\DTO\Tests\Fixtures\ScalarConstraintsDTO;

describe('DTO Contract Compliance', function (): void {
    it('DataTransferObject implements FromRequestDTO', function (): void {
        expect(MinimalDTO::class)->toImplement(FromRequestDTO::class);
    });

    it('DataTransferObject implements ValidatableDTO', function (): void {
        expect(MinimalDTO::class)->toImplement(ValidatableDTO::class);
    });

    it('DataTransferObject implements Arrayable', function (): void {
        expect(MinimalDTO::class)->toImplement(\Illuminate\Contracts\Support\Arrayable::class);
    });

    it('DataTransferObject implements JsonSerializable', function (): void {
        expect(MinimalDTO::class)->toImplement(\JsonSerializable::class);
    });

    it('DtoCollection implements ArrayAccess', function (): void {
        expect(DtoCollection::class)->toImplement(\ArrayAccess::class);
    });

    it('DtoCollection implements Countable', function (): void {
        expect(DtoCollection::class)->toImplement(\Countable::class);
    });

    it('DtoCollection implements IteratorAggregate', function (): void {
        expect(DtoCollection::class)->toImplement(\IteratorAggregate::class);
    });

    it('DtoCollection implements JsonSerializable', function (): void {
        expect(DtoCollection::class)->toImplement(\JsonSerializable::class);
    });

    it('rules() returns non-empty array for DTO with required fields', function (): void {
        $rules = MinimalDTO::rules();

        expect($rules)->toBeArray();
        expect($rules)->not->toBeEmpty();
        expect($rules)->toHaveKey('name');
        expect($rules['name'])->toContain('required');
    });

    it('rulesFor() returns same as rules() by default', function (): void {
        expect(MinimalDTO::rulesFor('create'))->toBe(MinimalDTO::rules());
        expect(MinimalDTO::rulesFor('update'))->toBe(MinimalDTO::rules());
        expect(MinimalDTO::rulesFor('patch'))->toBe(MinimalDTO::rules());
    });

    it('validateArray returns validated data', function (): void {
        $data = ['name' => 'Test', 'value' => 'hello'];

        $result = MinimalDTO::validateArray($data);

        expect($result)->toBe($data);
    });

    it('fromRequest is static and returns DTO instance type', function (): void {
        $request = new \Illuminate\Http\Request([
            'name' => 'Test',
            'value' => 'hello',
        ]);

        $dto = MinimalDTO::fromRequest($request);

        expect($dto)->toBeInstanceOf(MinimalDTO::class);
        expect($dto->name)->toBe('Test');
        expect($dto->value)->toBe('hello');
    });
});

describe('Validation Attribute Contract', function (): void {
    it('all validation attributes implement ValidationAttribute interface', function (): void {
        $attributes = [
            \ZeroBoiler\DTO\Attributes\Required::class,
            \ZeroBoiler\DTO\Attributes\Email::class,
            \ZeroBoiler\DTO\Attributes\Max::class,
            \ZeroBoiler\DTO\Attributes\Min::class,
            \ZeroBoiler\DTO\Attributes\Url::class,
            \ZeroBoiler\DTO\Attributes\Pattern::class,
            \ZeroBoiler\DTO\Attributes\In::class,
            \ZeroBoiler\DTO\Attributes\Integer::class,
            \ZeroBoiler\DTO\Attributes\Numeric::class,
            \ZeroBoiler\DTO\Attributes\Boolean::class,
            \ZeroBoiler\DTO\Attributes\Uuid::class,
            \ZeroBoiler\DTO\Attributes\Date::class,
            \ZeroBoiler\DTO\Attributes\Enum::class,
            \ZeroBoiler\DTO\Attributes\Confirmed::class,
            \ZeroBoiler\DTO\Attributes\Different::class,
            \ZeroBoiler\DTO\Attributes\Same::class,
            \ZeroBoiler\DTO\Attributes\Between::class,
            \ZeroBoiler\DTO\Attributes\ArrayRule::class,
            \ZeroBoiler\DTO\Attributes\Prohibited::class,
            \ZeroBoiler\DTO\Attributes\Present::class,
            \ZeroBoiler\DTO\Attributes\Declined::class,
            \ZeroBoiler\DTO\Attributes\Accepted::class,
            \ZeroBoiler\DTO\Attributes\StartsWith::class,
            \ZeroBoiler\DTO\Attributes\EndsWith::class,
            \ZeroBoiler\DTO\Attributes\Nullable::class,
            \ZeroBoiler\DTO\Attributes\Sometimes::class,
            \ZeroBoiler\DTO\Attributes\Distinct::class,
            \ZeroBoiler\DTO\Attributes\Size::class,
            \ZeroBoiler\DTO\Attributes\Json::class,
            \ZeroBoiler\DTO\Attributes\RequiredIf::class,
            \ZeroBoiler\DTO\Attributes\RequiredUnless::class,
            \ZeroBoiler\DTO\Attributes\RequiredWith::class,
            \ZeroBoiler\DTO\Attributes\RequiredWithAll::class,
            \ZeroBoiler\DTO\Attributes\RequiredWithout::class,
            \ZeroBoiler\DTO\Attributes\RequiredWithoutAll::class,
        ];

        foreach ($attributes as $attrClass) {
            expect($attrClass)->toImplement(ValidationAttribute::class);
        }
    });

    it('each validation attribute returns a non-empty ruleKey', function (): void {
        $instances = [
            new \ZeroBoiler\DTO\Attributes\Required(),
            new \ZeroBoiler\DTO\Attributes\Email(),
            new \ZeroBoiler\DTO\Attributes\Max(100),
            new \ZeroBoiler\DTO\Attributes\Min(1),
            new \ZeroBoiler\DTO\Attributes\Url(),
            new \ZeroBoiler\DTO\Attributes\Pattern('/^[a-z]+$/'),
            new \ZeroBoiler\DTO\Attributes\In(['a', 'b']),
            new \ZeroBoiler\DTO\Attributes\Integer(),
            new \ZeroBoiler\DTO\Attributes\Numeric(),
            new \ZeroBoiler\DTO\Attributes\Boolean(),
            new \ZeroBoiler\DTO\Attributes\Uuid(),
            new \ZeroBoiler\DTO\Attributes\Date(),
            new \ZeroBoiler\DTO\Attributes\Size(10),
            new \ZeroBoiler\DTO\Attributes\Json(),
            new \ZeroBoiler\DTO\Attributes\Confirmed(),
            new \ZeroBoiler\DTO\Attributes\Different('field'),
            new \ZeroBoiler\DTO\Attributes\Same('field'),
            new \ZeroBoiler\DTO\Attributes\Prohibited(),
            new \ZeroBoiler\DTO\Attributes\Present(),
            new \ZeroBoiler\DTO\Attributes\Declined(),
            new \ZeroBoiler\DTO\Attributes\Accepted(),
            new \ZeroBoiler\DTO\Attributes\StartsWith('pre'),
            new \ZeroBoiler\DTO\Attributes\EndsWith('suf'),
            new \ZeroBoiler\DTO\Attributes\Nullable(),
            new \ZeroBoiler\DTO\Attributes\Sometimes(),
            new \ZeroBoiler\DTO\Attributes\Distinct(),
        ];

        foreach ($instances as $instance) {
            expect($instance->ruleKey())->toBeString()->not->toBeEmpty();
        }
    });
});
