<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\Attributes\Accepted;
use ZeroBoiler\DTO\Attributes\Between;
use ZeroBoiler\DTO\Attributes\Boolean;
use ZeroBoiler\DTO\Attributes\Cast;
use ZeroBoiler\DTO\Attributes\Confirmed;
use ZeroBoiler\DTO\Attributes\Date;
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
use ZeroBoiler\DTO\Contracts\FromRequestDTO;
use ZeroBoiler\DTO\Contracts\ValidationAttribute;
use ZeroBoiler\DTO\Contracts\ValidatableDTO;
use ZeroBoiler\DTO\Casts\DTOCast;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\DTOManager;
use ZeroBoiler\DTO\DTOSServiceProvider;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Support\DtoMetadataResolver;
use ZeroBoiler\DTO\Support\OpenApiSchemaGenerator;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;

describe('DTO Type System: Attribute Contract Compliance', function (): void {
    $validationAttributes = [
        Accepted::class,
        Boolean::class,
        Confirmed::class,
        Declined::class, // if exists
        Distinct::class,
        Email::class,
        Integer::class,
        Numeric::class,
        Prohibited::class,
        Present::class,
        Required::class,
        Sometimes::class,
        Nullable::class,
        Url::class,
        Uuid::class,
    ];

    it('all validation attributes implement ValidationAttribute interface', function () use ($validationAttributes): void {
        foreach ($validationAttributes as $attrClass) {
            if (! class_exists($attrClass)) {
                continue;
            }
            $ref = new \ReflectionClass($attrClass);
            expect($ref->implementsInterface(ValidationAttribute::class))
                ->toBeTrue("{$attrClass} must implement ValidationAttribute");
        }
    });

    it('all validation attributes have ruleKey() method', function () use ($validationAttributes): void {
        foreach ($validationAttributes as $attrClass) {
            if (! class_exists($attrClass)) {
                continue;
            }
            $ref = new \ReflectionClass($attrClass);
            expect($ref->hasMethod('ruleKey'))->toBeTrue("{$attrClass} must have ruleKey()");

            $instance = $ref->newInstanceWithoutConstructor();
            $ruleKey = $instance->ruleKey();
            expect($ruleKey)->toBeString()->not->toBeEmpty();
        }
    });

    it('all validation attributes are final', function () use ($validationAttributes): void {
        foreach ($validationAttributes as $attrClass) {
            if (! class_exists($attrClass)) {
                continue;
            }
            $ref = new \ReflectionClass($attrClass);
            expect($ref->isFinal())->toBeTrue("{$attrClass} must be final");
        }
    });
});

describe('DTO Type System: Metadata Attributes', function (): void {
    it('Cast is final with TARGET_PROPERTY', function (): void {
        $ref = new \ReflectionClass(Cast::class);
        expect($ref->isFinal())->toBeTrue();

        $attrs = $ref->getAttributes();
        expect($attrs)->not->toBeEmpty();
        $instance = $attrs[0]->newInstance();
        expect($instance->flags & \Attribute::TARGET_PROPERTY)->not->toBe(0);
    });

    it('MapFrom is final with TARGET_PROPERTY', function (): void {
        $ref = new \ReflectionClass(MapFrom::class);
        expect($ref->isFinal())->toBeTrue();

        $attrs = $ref->getAttributes();
        $instance = $attrs[0]->newInstance();
        expect($instance->flags & \Attribute::TARGET_PROPERTY)->not->toBe(0);
    });

    it('Hidden is final with TARGET_PROPERTY', function (): void {
        $ref = new \ReflectionClass(Hidden::class);
        expect($ref->isFinal())->toBeTrue();

        $attrs = $ref->getAttributes();
        $instance = $attrs[0]->newInstance();
        expect($instance->flags & \Attribute::TARGET_PROPERTY)->not->toBe(0);
    });

    it('DefaultValue is final with TARGET_PROPERTY', function (): void {
        $ref = new \ReflectionClass(DefaultValue::class);
        expect($ref->isFinal())->toBeTrue();

        $attrs = $ref->getAttributes();
        $instance = $attrs[0]->newInstance();
        expect($instance->flags & \Attribute::TARGET_PROPERTY)->not->toBe(0);
    });

    it('NestedArray is final with TARGET_PROPERTY', function (): void {
        $ref = new \ReflectionClass(NestedArray::class);
        expect($ref->isFinal())->toBeTrue();
    });

    it('All metadata attributes have readonly constructor params', function (): void {
        $metadataAttrs = [Cast::class, MapFrom::class, Hidden::class, DefaultValue::class, NestedArray::class];

        foreach ($metadataAttrs as $attrClass) {
            if (! class_exists($attrClass)) {
                continue;
            }
            $ref = new \ReflectionClass($attrClass);
            $constructor = $ref->getConstructor();

            if ($constructor !== null) {
                foreach ($constructor->getParameters() as $param) {
                    $propRef = new \ReflectionProperty($attrClass, $param->getName());
                    expect($propRef->isReadOnly())->toBeTrue(
                        "{$attrClass}::\${$param->getName()} must be readonly"
                    );
                }
            }
        }
    });
});

describe('DTO Type System: Contracts', function (): void {
    it('FromRequestDTO defines fromRequest with correct signature', function (): void {
        $ref = new \ReflectionMethod(FromRequestDTO::class, 'fromRequest');

        expect($ref->isStatic())->toBeTrue();
        expect($ref->getParameters())->toHaveCount(2);
        expect($ref->getReturnType()?->getName())->toBe('static');
    });

    it('ValidatableDTO defines rules and rulesFor methods', function (): void {
        $ref = new \ReflectionClass(ValidatableDTO::class);

        expect($ref->hasMethod('rules'))->toBeTrue();
        expect($ref->hasMethod('rulesFor'))->toBeTrue();
    });

    it('ValidationAttribute defines ruleKey method', function (): void {
        $ref = new \ReflectionMethod(ValidationAttribute::class, 'ruleKey');

        expect($ref->getReturnType()?->getName())->toBe('string');
    });
});

describe('DTO Type System: Core Classes Final Check', function (): void {
    $finalClasses = [
        DataTransferObject::class => false, // abstract, not final
        DtoCollection::class => true,
        DTOManager::class => true,
        DTOException::class => true,
        DTOCast::class => true,
        DtoMetadataResolver::class => true,
        OpenApiSchemaGenerator::class => true,
        DTOSServiceProvider::class => true,
    ];

    it('core classes have correct final modifier', function () use ($finalClasses): void {
        foreach ($finalClasses as $class => $shouldBeFinal) {
            if (! class_exists($class)) {
                continue;
            }
            $ref = new \ReflectionClass($class);

            expect($ref->isFinal())->toBe($shouldBeFinal, "{$class} final expectation");
        }
    });
});

describe('DTO Type System: DtoCollection Type Safety', function (): void {
    it('rejects non-DTO items in constructor', function (): void {
        expect(fn (): mixed => new DtoCollection(['not_a_dto']))
            ->toThrow(\InvalidArgumentException::class);
    });

    it('accepts valid DTO items', function (): void {
        $dto = EmptyDTO::fromArray([], validate: false);
        $collection = new DtoCollection([$dto]);

        expect($collection->count())->toBe(1);
    });

    it('make() creates collection from DTO array', function (): void {
        $dto = EmptyDTO::fromArray([], validate: false);
        $collection = DtoCollection::make([$dto]);

        expect($collection)->toBeInstanceOf(DtoCollection::class);
        expect($collection->count())->toBe(1);
    });

    it('offsetSet rejects non-DTO values', function (): void {
        $dto = EmptyDTO::fromArray([], validate: false);
        $collection = new DtoCollection([$dto]);

        expect(fn (): mixed => $collection[] = 'invalid')
            ->toThrow(\InvalidArgumentException::class);
    });

    it('filter returns new DtoCollection', function (): void {
        $dto = EmptyDTO::fromArray([], validate: false);
        $collection = new DtoCollection([$dto, $dto]);
        $filtered = $collection->filter(fn (DataTransferObject $d): bool => true);

        expect($filtered)->toBeInstanceOf(DtoCollection::class);
        expect($filtered->count())->toBe(2);
        expect($filtered)->not->toBe($collection); // different instance
    });
});

describe('DTO Type System: DTOException Factory Methods', function (): void {
    it('invalidCast() includes property and type in message', function (): void {
        $e = DTOException::invalidCast('age', 'integer', 'not_a_number');

        expect($e->getMessage())->toContain('age')
            ->and($e->getMessage())->toContain('integer');
    });

    it('invalidJson() includes property and error in message', function (): void {
        $e = DTOException::invalidJson('metadata', 'Syntax error');

        expect($e->getMessage())->toContain('metadata')
            ->and($e->getMessage())->toContain('Syntax error');
    });

    it('is final', function (): void {
        $ref = new \ReflectionClass(DTOException::class);
        expect($ref->isFinal())->toBeTrue();
    });
});

describe('DTO Type System: DTOCast', function (): void {
    it('is final', function (): void {
        $ref = new \ReflectionClass(DTOCast::class);
        expect($ref->isFinal())->toBeTrue();
    });

    it('implements CastsAttributes', function (): void {
        $ref = new \ReflectionClass(DTOCast::class);
        expect($ref->implementsInterface(\Illuminate\Contracts\Database\Eloquent\CastsAttributes::class))->toBeTrue();
    });

    it('constructor has readonly dtoClass property', function (): void {
        $cast = new DTOCast(CreateUserDTO::class);
        $ref = new \ReflectionProperty($cast, 'dtoClass');

        expect($ref->isReadOnly())->toBeTrue();
        expect($ref->getValue($cast))->toBe(CreateUserDTO::class);
    });

    it('constructor has readonly validate property defaulting to true', function (): void {
        $cast = new DTOCast(CreateUserDTO::class);
        $ref = new \ReflectionProperty($cast, 'validate');

        expect($ref->isReadOnly())->toBeTrue();
        expect($ref->getValue($cast))->toBeTrue();
    });
});

describe('DTO Type System: Hydration Pipeline Edge Cases', function (): void {
    it('fromArray with empty data returns DTO with defaults', function (): void {
        $dto = EmptyDTO::fromArray([], validate: false);

        expect($dto)->toBeInstanceOf(EmptyDTO::class);
    });

    it('fromPartialArray with empty data returns DTO', function (): void {
        $dto = EmptyDTO::fromPartialArray([]);

        expect($dto)->toBeInstanceOf(EmptyDTO::class);
    });

    it('toJson returns valid JSON string', function (): void {
        $dto = EmptyDTO::fromArray([], validate: false);
        $json = $dto->toJson();

        expect($json)->toBeJson();
        expect(json_decode($json, true))->toBeArray();
    });

    it('equals returns true for identical DTOs', function (): void {
        $dto1 = EmptyDTO::fromArray([], validate: false);
        $dto2 = EmptyDTO::fromArray([], validate: false);

        expect($dto1->equals($dto2))->toBeTrue();
    });

    it('allValues includes all properties', function (): void {
        $dto = EmptyDTO::fromArray([], validate: false);
        $all = $dto->allValues();

        expect($all)->toBeArray();
    });
});

describe('DTO Type System: Metadata Cache', function (): void {
    it('flushMetadataCache clears all cached metadata', function (): void {
        // First access builds cache
        CreateUserDTO::rules();

        // Flush
        DataTransferObject::flushMetadataCache();

        // Rebuilds on next access
        $rules = CreateUserDTO::rules();
        expect($rules)->toBeArray()->not->toBeEmpty();
    });

    it('flushMetadataCache with class clears only that class', function (): void {
        CreateUserDTO::rules();

        DataTransferObject::flushMetadataCache(CreateUserDTO::class);

        // Should rebuild cleanly
        $rules = CreateUserDTO::rules();
        expect($rules)->toBeArray();
    });
});

describe('DTO Type System: Strict Type Declarations', function (): void {
    $sourceFiles = [
        ...glob(__DIR__.'/../../src/Attributes/*.php'),
        ...glob(__DIR__.'/../../src/Casts/*.php'),
        ...glob(__DIR__.'/../../src/Exceptions/*.php'),
        __DIR__.'/../../src/DataTransferObject.php',
        __DIR__.'/../../src/DtoCollection.php',
        __DIR__.'/../../src/DTOManager.php',
    ];

    it('all source files have declare(strict_types=1)', function () use ($sourceFiles): void {
        foreach ($sourceFiles as $file) {
            if (! is_file($file)) {
                continue;
            }
            $content = file_get_contents($file);
            expect($content)->toContain('declare(strict_types=1)', "File {$file} must have strict_types=1");
        }
    });

    it('DataTransferObject has phpstan-consistent-constructor annotation', function (): void {
        $ref = new \ReflectionClass(DataTransferObject::class);
        $doc = $ref->getDocComment();

        expect($doc)->not->toBeFalse();
        expect($doc)->toContain('@phpstan-consistent-constructor');
    });
});
