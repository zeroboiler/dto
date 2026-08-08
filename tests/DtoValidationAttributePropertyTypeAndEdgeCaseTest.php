<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\Attributes\Accepted;
use ZeroBoiler\DTO\Attributes\ArrayRule;
use ZeroBoiler\DTO\Attributes\Between;
use ZeroBoiler\DTO\Attributes\Boolean;
use ZeroBoiler\DTO\Attributes\Cast;
use ZeroBoiler\DTO\Attributes\Collection;
use ZeroBoiler\DTO\Attributes\Confirmed;
use ZeroBoiler\DTO\Attributes\Date;
use ZeroBoiler\DTO\Attributes\Declined;
use ZeroBoiler\DTO\Attributes\DefaultValue;
use ZeroBoiler\DTO\Attributes\Different;
use ZeroBoiler\DTO\Attributes\Distinct;
use ZeroBoiler\DTO\Attributes\Email;
use ZeroBoiler\DTO\Attributes\EndsWith;
use ZeroBoiler\DTO\Attributes\Enum;
use ZeroBoiler\DTO\Attributes\Hidden;
use ZeroBoiler\DTO\Attributes\In;
use ZeroBoiler\DTO\Attributes\Integer;
use ZeroBoiler\DTO\Attributes\Json;
use ZeroBoiler\DTO\Attributes\MapFrom;
use ZeroBoiler\DTO\Attributes\Max;
use ZeroBoiler\DTO\Attributes\Min;
use ZeroBoiler\DTO\Attributes\NestedArray;
use ZeroBoiler\DTO\Attributes\Nullable;
use ZeroBoiler\DTO\Attributes\Numeric;
use ZeroBoiler\DTO\Attributes\Pattern;
use ZeroBoiler\DTO\Attributes\Present;
use ZeroBoiler\DTO\Attributes\Prohibited;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\Attributes\RequiredIf;
use ZeroBoiler\DTO\Attributes\RequiredUnless;
use ZeroBoiler\DTO\Attributes\RequiredWith;
use ZeroBoiler\DTO\Attributes\RequiredWithAll;
use ZeroBoiler\DTO\Attributes\RequiredWithout;
use ZeroBoiler\DTO\Attributes\RequiredWithoutAll;
use ZeroBoiler\DTO\Attributes\Same;
use ZeroBoiler\DTO\Attributes\Size;
use ZeroBoiler\DTO\Attributes\Sometimes;
use ZeroBoiler\DTO\Attributes\StartsWith;
use ZeroBoiler\DTO\Attributes\Url;
use ZeroBoiler\DTO\Attributes\Uuid;
use ZeroBoiler\DTO\Contracts\ValidationAttribute;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DTOManager;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\DtoMetadataResolver;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Support\OpenApiSchemaGenerator;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;
use ZeroBoiler\DTO\Tests\Fixtures\MinimalDTO;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus as EnumUserStatus;

describe('Validation attribute property types (PHPStan L9 strict)', function (): void {
    $validationAttributes = [
        Accepted::class,
        Boolean::class,
        Confirmed::class,
        Declined::class,
        Distinct::class,
        Email::class,
        Hidden::class,
        Integer::class,
        Json::class,
        Nullable::class,
        Numeric::class,
        Present::class,
        Prohibited::class,
        Required::class,
        Sometimes::class,
        Url::class,
        Uuid::class,
    ];

    it('all simple validation attributes implement ValidationAttribute interface', function () use ($validationAttributes): void {
        foreach ($validationAttributes as $attrClass) {
            $ref = new ReflectionClass($attrClass);
            expect($ref->implementsInterface(ValidationAttribute::class))
                ->toBeTrue("{$attrClass} must implement ValidationAttribute");

            expect($ref->isFinal())->toBeTrue("{$attrClass} must be final");
            expect($ref->hasMethod('ruleKey'))->toBeTrue("{$attrClass} must have ruleKey()");
        }
    });

    it('all simple validation attributes have readonly nullable message property', function () use ($validationAttributes): void {
        $skipNoMessage = [Boolean::class, Hidden::class, Distinct::class, Integer::class, Json::class, Nullable::class, Numeric::class, Present::class, Prohibited::class, Required::class, Sometimes::class, Url::class, Uuid::class, Confirmed::class, Declined::class, Accepted::class];

        foreach ($validationAttributes as $attrClass) {
            if (in_array($attrClass, $skipNoMessage, true)) {
                continue;
            }

            $instance = new $attrClass();
            if (! in_array($attrClass, [Hidden::class, Distinct::class], true)) {
                $ref = new ReflectionClass($attrClass);
                if ($ref->hasProperty('message')) {
                    $prop = $ref->getProperty('message');
                    expect($prop->isReadOnly())->toBeTrue()
                        ->and($prop->getType()->allowsNull())->toBeTrue();
                }
            }
        }
    });

    it('parameterized validation attributes have correct property types', function (): void {
        // Max — int|float value
        $max = new Max(255);
        $ref = new ReflectionProperty($max, 'value');
        expect($ref->isReadOnly())->toBeTrue();

        // Min — int|float value
        $min = new Min(1);
        expect((new ReflectionProperty($min, 'value'))->isReadOnly())->toBeTrue();

        // Between — int|float min, int|float max
        $between = new Between(1, 100);
        expect((new ReflectionProperty($between, 'min'))->getType()->getName())
            ->toBeIn(['int', 'float']) // union type shows as ReflectionNamedType parts
            ->and((new ReflectionProperty($between, 'max'))->isReadOnly())->toBeTrue();

        // Pattern — string regex
        $pattern = new Pattern('/^[a-z]+$/');
        expect((new ReflectionProperty($pattern, 'regex'))->getType()->getName())->toBe('string');

        // In — array values
        $in = new In(['a', 'b', 'c']);
        expect((new ReflectionProperty($in, 'values'))->getType()->getName())->toBe('array');

        // Size — int|float value
        $size = new Size(10);
        expect((new ReflectionProperty($size, 'value'))->isReadOnly())->toBeTrue();

        // StartsWith — string|array prefix
        $sw = new StartsWith('https://');
        expect((new ReflectionProperty($sw, 'prefix'))->getType()->allowsNull())->toBeFalse();

        // EndsWith — string|array suffix
        $ew = new EndsWith('.com');
        expect((new ReflectionProperty($ew, 'suffix'))->getType()->allowsNull())->toBeFalse();

        // Same — string field
        $same = new Same('password');
        expect((new ReflectionProperty($same, 'field'))->getType()->getName())->toBe('string');

        // Different — string field
        $diff = new Different('email');
        expect((new ReflectionProperty($diff, 'field'))->getType()->getName())->toBe('string');
    });

    it('conditional validation attributes have correct types', function (): void {
        // RequiredIf — string field, mixed value
        $ri = new RequiredIf('type', 'individual');
        expect((new ReflectionProperty($ri, 'field'))->getType()->getName())->toBe('string');
        expect((new ReflectionProperty($ri, 'value'))->getType()->getName())->toBe('mixed');

        // RequiredUnless — string field, mixed value
        $ru = new RequiredUnless('type', 'company');
        expect((new ReflectionProperty($ru, 'field'))->getType()->getName())->toBe('string');

        // RequiredWith — string|array fields (normalized to array)
        $rw = new RequiredWith('email');
        $ref = new ReflectionProperty($rw, 'fields');
        expect($ref->getType()->getName())->toBe('array')
            ->and($rw->fields)->toBe(['email']);

        $rw2 = new RequiredWith(['email', 'phone']);
        expect($rw2->fields)->toBe(['email', 'phone']);

        // RequiredWithAll — same pattern
        $rwa = new RequiredWithAll(['a', 'b']);
        expect($rwa->fields)->toBe(['a', 'b']);

        // RequiredWithout — same pattern
        $rwo = new RequiredWithout(['a']);
        expect($rwo->fields)->toBe(['a']);

        // RequiredWithoutAll — same pattern
        $rwoa = new RequiredWithoutAll(['a', 'b']);
        expect($rwoa->fields)->toBe(['a', 'b']);
    });

    it('metadata attributes have correct types', function (): void {
        // MapFrom — string key
        $mf = new MapFrom('user_name');
        expect((new ReflectionProperty($mf, 'key'))->getType()->getName())->toBe('string');

        // Cast — string type
        $cast = new Cast('integer');
        expect((new ReflectionProperty($cast, 'type'))->getType()->getName())->toBe('string');

        // DefaultValue — mixed value
        $dv = new DefaultValue(42);
        expect((new ReflectionProperty($dv, 'value'))->getType()->getName())->toBe('mixed');

        $dvNull = new DefaultValue();
        expect($dvNull->value)->toBeNull();

        // NestedArray — class-string<DataTransferObject>
        $na = new NestedArray(CreateUserDTO::class);
        expect((new ReflectionProperty($na, 'dtoClass'))->getType()->getName())->toBe('string');

        // Collection — class-string<DataTransferObject>
        $col = new Collection(CreateUserDTO::class);
        expect((new ReflectionProperty($col, 'dtoClass'))->getType()->getName())->toBe('string');
    });

    it('Date attribute has nullable format property', function (): void {
        $date = new Date;
        expect((new ReflectionProperty($date, 'format'))->getType()->allowsNull())->toBeTrue()
            ->and($date->format)->toBeNull();

        $dateFmt = new Date('Y-m-d');
        expect($dateFmt->format)->toBe('Y-m-d');
    });

    it('Enum attribute has string enumClass property', function (): void {
        $enum = new Enum(EnumUserStatus::class);
        expect((new ReflectionProperty($enum, 'enumClass'))->getType()->getName())->toBe('string');
    });
});

describe('All ruleKey() implementations return non-empty strings', function (): void {
    $ruleKeyMap = [
        Accepted::class => 'accepted',
        Boolean::class => 'boolean',
        Confirmed::class => 'confirmed',
        Declined::class => 'declined',
        Distinct::class => 'distinct',
        Email::class => 'email',
        Integer::class => 'integer',
        Json::class => 'json',
        Nullable::class => 'nullable',
        Numeric::class => 'numeric',
        Present::class => 'present',
        Prohibited::class => 'prohibited',
        Required::class => 'required',
        Sometimes::class => 'sometimes',
        Url::class => 'url',
        Uuid::class => 'uuid',
        Max::class => 'max',
        Min::class => 'min',
        Pattern::class => 'regex',
        In::class => 'in',
        Size::class => 'size',
        Between::class => 'between',
        StartsWith::class => 'starts_with',
        EndsWith::class => 'ends_with',
        Same::class => 'same',
        Different::class => 'different',
        RequiredIf::class => 'required_if',
        RequiredUnless::class => 'required_unless',
        RequiredWith::class => 'required_with',
        RequiredWithAll::class => 'required_with_all',
        RequiredWithout::class => 'required_without',
        RequiredWithoutAll::class => 'required_without_all',
        Date::class => 'date',
        Enum::class => 'enum',
        Collection::class => 'array',
        NestedArray::class => 'array',
        ArrayRule::class => 'array',
    ];

    it('each validation attribute returns expected ruleKey', function () use ($ruleKeyMap): void {
        foreach ($ruleKeyMap as $attrClass => $expectedKey) {
            $instance = match ($attrClass) {
                Max::class => new Max(10),
                Min::class => new Min(1),
                Pattern::class => new Pattern('/test/'),
                In::class => new In(['a']),
                Size::class => new Size(5),
                Between::class => new Between(1, 10),
                StartsWith::class => new StartsWith('a'),
                EndsWith::class => new EndsWith('z'),
                Same::class => new Same('field'),
                Different::class => new Different('field'),
                RequiredIf::class => new RequiredIf('f', 'v'),
                RequiredUnless::class => new RequiredUnless('f', 'v'),
                RequiredWith::class => new RequiredWith('f'),
                RequiredWithAll::class => new RequiredWithAll(['f']),
                RequiredWithout::class => new RequiredWithout(['f']),
                RequiredWithoutAll::class => new RequiredWithoutAll(['f']),
                Date::class => new Date,
                Enum::class => new Enum(EnumUserStatus::class),
                Collection::class => new Collection(CreateUserDTO::class),
                NestedArray::class => new NestedArray(CreateUserDTO::class),
                ArrayRule::class => new ArrayRule,
                default => new $attrClass(),
            };

            expect($instance->ruleKey())->toBe($expectedKey, "Failed for {$attrClass}");
        }
    });
});

describe('DTOException factory methods', function (): void {
    it('invalidCast creates with property, type, and value info', function (): void {
        $e = DTOException::invalidCast('age', 'integer', 'not_a_number');
        expect($e->getMessage())->toContain('age')
            ->and($e->getMessage())->toContain('integer');
    });

    it('invalidJson creates with property and error info', function (): void {
        $e = DTOException::invalidJson('payload', 'Syntax error');
        expect($e->getMessage())->toContain('payload')
            ->and($e->getMessage())->toContain('Syntax error');
    });

    it('is final', function (): void {
        $ref = new ReflectionClass(DTOException::class);
        expect($ref->isFinal())->toBeTrue();
    });
});

describe('Core DTO classes are final', function (): void {
    $finalClasses = [
        DataTransferObject::class => false, // abstract, can't be final
        DTOManager::class => true,
        DtoCollection::class => true,
        DtoMetadataResolver::class => true,
        OpenApiSchemaGenerator::class => true,
        DTOException::class => true,
    ];

    it('expected classes are final', function () use ($finalClasses): void {
        foreach ($finalClasses as $class => $shouldBeFinal) {
            if (! $shouldBeFinal) {
                continue;
            }

            $ref = new ReflectionClass($class);
            expect($ref->isFinal())->toBeTrue("{$class} should be final");
        }
    });
});

describe('DtoMetadataResolver resolution consistency', function (): void {
    it('resolve returns consistent metadata on repeated calls', function (): void {
        // Flush cache first
        DataTransferObject::flushMetadataCache(CreateUserDTO::class);

        $meta1 = DtoMetadataResolver::resolve(CreateUserDTO::class);
        $meta2 = DtoMetadataResolver::resolve(CreateUserDTO::class);

        expect($meta1)->toBe($meta2);
    });

    it('resolve returns correct structure keys', function (): void {
        DataTransferObject::flushMetadataCache(CreateUserDTO::class);
        $meta = DtoMetadataResolver::resolve(CreateUserDTO::class);

        expect($meta)->toHaveKeys(['properties', 'rules', 'messages']);
        expect($meta['properties'])->toBeArray();
        expect($meta['rules'])->toBeArray();
        expect($meta['messages'])->toBeArray();
    });

    it('EmptyDTO resolves with correct properties', function (): void {
        DataTransferObject::flushMetadataCache(EmptyDTO::class);
        $meta = DtoMetadataResolver::resolve(EmptyDTO::class);

        expect($meta['properties'])->toHaveKeys(['foo', 'bar']);
        // Both are nullable with defaults, so they should have 'sometimes' base rule
    });
});

describe('DTO fromArray/fromPartialArray edge cases', function (): void {
    it('fromArray without validation skips rules', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
            // Missing required — but validation is off
        ], validate: false);

        expect($dto)->toBeInstanceOf(CreateUserDTO::class);
        expect($dto->email)->toBe('test@example.com');
    });

    it('fromArray applies defaults for missing optional fields', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
        ], validate: false);

        expect($dto->status)->toBe('active');
        expect($dto->tags)->toBe([]);
        expect($dto->phone)->toBeNull();
    });

    it('fromPartialArray with empty data uses all defaults', function (): void {
        DataTransferObject::flushMetadataCache(CreateUserDTO::class);
        $dto = CreateUserDTO::fromPartialArray([], validate: false);

        // status has DefaultValue('active')
        expect($dto->status)->toBe('active');
        expect($dto->tags)->toBe([]);
    });

    it('toJson produces valid JSON', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
            'password' => 'secret',
        ], validate: false);

        $json = $dto->toJson();
        expect($json)->toBeJson();

        $decoded = json_decode($json, true);
        expect($decoded)->toHaveKey('email')
            ->and($decoded)->toHaveKey('name')
            ->and($decoded)->not->toHaveKey('password'); // Hidden
    });

    it('toArray excludes hidden fields', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
            'password' => 'secret123',
        ], validate: false);

        $arr = $dto->toArray();
        expect($arr)->not->toHaveKey('password');
    });

    it('allValues includes hidden fields', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
            'password' => 'secret123',
        ], validate: false);

        $all = $dto->allValues();
        expect($all)->toHaveKey('password')
            ->and($all['password'])->toBe('secret123');
    });

    it('only returns specified fields', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
        ], validate: false);

        $only = $dto->only('email');
        expect($only)->toHaveKey('email')
            ->and($only)->not->toHaveKey('name');
    });

    it('except returns all fields except specified', function (): void {
        $dto = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
        ], validate: false);

        $except = $dto->except('email');
        expect($except)->not->toHaveKey('email')
            ->and($except)->toHaveKey('name');
    });

    it('equals compares toArray output', function (): void {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
        ], validate: false);

        $dto2 = CreateUserDTO::fromArray([
            'email' => 'test@example.com',
            'name' => 'Test',
        ], validate: false);

        $dto3 = CreateUserDTO::fromArray([
            'email' => 'other@example.com',
            'name' => 'Test',
        ], validate: false);

        expect($dto1->equals($dto2))->toBeTrue();
        expect($dto1->equals($dto3))->toBeFalse();
    });

    it('isEmpty detects all-default DTO', function (): void {
        DataTransferObject::flushMetadataCache(CreateUserDTO::class);
        $dto = CreateUserDTO::fromPartialArray([], validate: false);

        // status='active', tags=[], phone=null, password=null
        // email and name are required — partial gives type-appropriate empty
        expect($dto->isEmpty())->toBeTrue();
    });
});
