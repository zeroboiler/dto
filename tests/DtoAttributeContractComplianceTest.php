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
use ZeroBoiler\DTO\Attributes\Enum as EnumAttribute;
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

/**
 * Verify that all validation attributes implement ValidationAttribute interface
 * and return a valid ruleKey(). Also verify final/readonly class properties.
 */
describe('DTO attribute contract compliance', function () {
    /**
     * @var list<class-string> Validation attribute classes that must implement ValidationAttribute
     */
    $validationAttributes = [
        Required::class,
        Email::class,
        Max::class,
        Min::class,
        Url::class,
        Pattern::class,
        In::class,
        Integer::class,
        Numeric::class,
        Boolean::class,
        Uuid::class,
        Date::class,
        ArrayRule::class,
        Json::class,
        EnumAttribute::class,
        Confirmed::class,
        Same::class,
        Different::class,
        Between::class,
        Prohibited::class,
        Present::class,
        Declined::class,
        Accepted::class,
        StartsWith::class,
        EndsWith::class,
        Nullable::class,
        Sometimes::class,
        Distinct::class,
        Size::class,
        RequiredIf::class,
        RequiredUnless::class,
        RequiredWith::class,
        RequiredWithAll::class,
        RequiredWithout::class,
        RequiredWithoutAll::class,
    ];

    foreach ($validationAttributes as $attrClass) {
        it("{$attrClass} implements ValidationAttribute", function () use ($attrClass) {
            expect($attrClass)->toImplement(ValidationAttribute::class);
        });

        it("{$attrClass} returns non-empty ruleKey()", function () use ($attrClass) {
            $ref = new \ReflectionClass($attrClass);
            $constructor = $ref->getConstructor();

            // Build constructor args with defaults
            $args = [];
            if ($constructor !== null) {
                foreach ($constructor->getParameters() as $param) {
                    if ($param->isDefaultValueAvailable()) {
                        $args[] = $param->getDefaultValue();
                    } else {
                        // Provide dummy values for required params
                        $type = $param->getType();
                        if ($type instanceof \ReflectionNamedType) {
                            $args[] = match ($type->getName()) {
                                'string' => 'test',
                                'int' => 1,
                                'array' => [],
                                default => 'test',
                            };
                        } else {
                            $args[] = 'test';
                        }
                    }
                }
            }

            $instance = new $attrClass(...$args);
            expect($instance->ruleKey())->toBeString()->not->toBeEmpty();
        });

        it("{$attrClass} is a final class", function () use ($attrClass) {
            $ref = new \ReflectionClass($attrClass);
            expect($ref->isFinal())->toBeTrue();
        });
    }

    // Non-validation attributes (metadata attributes)
    $metadataAttributes = [Cast::class, MapFrom::class, Hidden::class, DefaultValue::class, NestedArray::class, Collection::class];

    foreach ($metadataAttributes as $attrClass) {
        it("{$attrClass} is a final class", function () use ($attrClass) {
            $ref = new \ReflectionClass($attrClass);
            expect($ref->isFinal())->toBeTrue();
        });

        it("{$attrClass} has readonly promoted properties only", function () use ($attrClass) {
            $ref = new \ReflectionClass($attrClass);
            foreach ($ref->getProperties() as $prop) {
                expect($prop->isReadOnly())->toBeTrue("{$attrClass}::\${$prop->getName()} must be readonly");
                expect($prop->isPublic())->toBeTrue("{$attrClass}::\${$prop->getName()} must be public");
            }
        });
    }

    it('all validation attributes have a message property', function () use ($validationAttributes) {
        foreach ($validationAttributes as $attrClass) {
            $ref = new \ReflectionClass($attrClass);
            $props = $ref->getProperties();
            $hasMessage = false;
            foreach ($props as $prop) {
                if ($prop->getName() === 'message') {
                    $hasMessage = true;
                    break;
                }
            }
            expect($hasMessage)->toBeTrue("{$attrClass} must have a 'message' property for custom validation messages");
        }
    });

    it('all validation attributes have TARGET_PROPERTY', function () use ($validationAttributes) {
        foreach ($validationAttributes as $attrClass) {
            $ref = new \ReflectionClass($attrClass);
            $attrs = $ref->getAttributes(\Attribute::class);
            $hasTargetProperty = false;
            foreach ($attrs as $attr) {
                $instance = $attr->newInstance();
                if (($instance->flags & Attribute::TARGET_PROPERTY) !== 0) {
                    $hasTargetProperty = true;
                    break;
                }
            }
            expect($hasTargetProperty)->toBeTrue("{$attrClass} must target properties");
        }
    });
});
