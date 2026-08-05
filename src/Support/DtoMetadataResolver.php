<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Support;

use Illuminate\Validation\Rules\Enum as EnumRule;
use ReflectionClass;
use ReflectionProperty;
use ZeroBoiler\DTO\Attributes\Accepted;
use ZeroBoiler\DTO\Attributes\ArrayRule;
use ZeroBoiler\DTO\Attributes\Between;
use ZeroBoiler\DTO\Attributes\Boolean;
use ZeroBoiler\DTO\Attributes\Cast as CastAttribute;
use ZeroBoiler\DTO\Attributes\Collection;
use ZeroBoiler\DTO\Attributes\Confirmed;
use ZeroBoiler\DTO\Attributes\Date as DateAttribute;
use ZeroBoiler\DTO\Attributes\Declined;
use ZeroBoiler\DTO\Attributes\DefaultValue as DefaultValueAttribute;
use ZeroBoiler\DTO\Attributes\Different;
use ZeroBoiler\DTO\Attributes\Distinct;
use ZeroBoiler\DTO\Attributes\Email;
use ZeroBoiler\DTO\Attributes\EndsWith;
use ZeroBoiler\DTO\Attributes\Enum as EnumAttribute;
use ZeroBoiler\DTO\Attributes\Hidden;
use ZeroBoiler\DTO\Attributes\In;
use ZeroBoiler\DTO\Attributes\Integer;
use ZeroBoiler\DTO\Attributes\Json as JsonAttribute;
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
use ZeroBoiler\ValueObjects\Contracts\ValueObject as ValueObjectContract;

/**
 * Resolves validation rules and property metadata from DTO attributes.
 *
 * Extracted from DataTransferObject to reduce complexity.
 */
final class DtoMetadataResolver
{
    /**
     * Resolve all metadata for a DTO class.
     *
     * @param  class-string  $class
     * @return array{
     *     properties: array<non-empty-string, array<string, mixed>>,
     *     rules: array<string, array<int, mixed>>,
     *     messages: array<string, string>
     * }
     */
    public static function resolve(string $class): array
    {
        $reflection = new ReflectionClass($class);
        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            return ['properties' => [], 'rules' => [], 'messages' => []];
        }

        $properties = [];
        $rules = [];
        /** @var array<string, string> $messages */
        $messages = [];
        /** @var array<string, array<int, mixed>> $extraRules */
        $extraRules = [];

        foreach ($constructor->getParameters() as $param) {
            $name = $param->getName();

            if (! $reflection->hasProperty($name)) {
                continue;
            }

            $propReflection = new ReflectionProperty($class, $name);
            $type = $param->getType();

            $propMeta = [
                'map_from' => null,
                'default' => null,
                'has_default' => $param->isDefaultValueAvailable(),
                'cast' => null,
                'hidden' => false,
                'nullable' => $type?->allowsNull() ?? true,
                'value_object_class' => self::detectValueObjectClass($type),
                'dto_class' => self::detectDtoClass($type),
                'enum_class' => self::detectEnumClass($type),
                'nested_array_class' => null,
                'collection_class' => null,
            ];

            if ($propMeta['has_default']) {
                $propMeta['default'] = $param->getDefaultValue();
            }

            $propRules = self::inferBaseRules($type, $propMeta['nullable'], $propMeta['has_default']);

            self::resolveAttributes($propReflection, $propMeta, $propRules, $messages, $extraRules, $name);

            $properties[$name] = $propMeta;
            if ($propRules !== []) {
                $rules[$name] = array_unique($propRules);
            }
        }

        // Merge wildcard rules (e.g., tags.* => ['distinct'])
        foreach ($extraRules as $field => $fieldRules) {
            $rules[$field] = $fieldRules;
        }

        return ['properties' => $properties, 'rules' => $rules, 'messages' => $messages];
    }

    /**
     * @param  list<string|EnumRule>  $propRules
     * @param  array<string, mixed>  $propMeta
     * @param  array<string, string>  $messages
     * @param  array<string, array<int, mixed>>  $extraRules
     */
    private static function resolveAttributes(
        ReflectionProperty $propReflection,
        array &$propMeta,
        array &$propRules,
        array &$messages,
        array &$extraRules,
        string $name,
    ): void {
        foreach ($propReflection->getAttributes() as $attr) {
            $instance = $attr->newInstance();

            self::applyValidationAttribute($instance, $propRules);
            self::applyMetaAttribute($instance, $propMeta);

            // Distinct needs a wildcard rule on the array elements
            if ($instance instanceof Distinct) {
                $extraRules["{$name}.*"] = ['distinct'];
            }

            if ($instance instanceof DefaultValueAttribute) {
                $propMeta['default'] = $instance->value;
                $propMeta['has_default'] = true;
            }

            self::collectMessage($instance, $messages, $name);
        }
    }

    /**
     * @param  list<string|EnumRule>  $propRules
     */
    private static function applyValidationAttribute(object $instance, array &$propRules): void
    {
        match (true) {
            $instance instanceof Required => $propRules[] = 'required',
            $instance instanceof Email => $propRules[] = 'email',
            $instance instanceof Max => $propRules[] = 'max:'.$instance->value,
            $instance instanceof Min => $propRules[] = 'min:'.$instance->value,
            $instance instanceof Url => $propRules[] = 'url',
            $instance instanceof Pattern => $propRules[] = 'regex:'.$instance->regex,
            $instance instanceof In => $propRules[] = 'in:'.implode(',', $instance->values),
            $instance instanceof Integer => $propRules[] = 'integer',
            $instance instanceof Numeric => $propRules[] = 'numeric',
            $instance instanceof Boolean => $propRules[] = 'boolean',
            $instance instanceof Uuid => $propRules[] = 'uuid',
            $instance instanceof DateAttribute => $propRules[] = $instance->format
                ? 'date_format:'.$instance->format
                : 'date',
            $instance instanceof EnumAttribute => $propRules[] = new EnumRule($instance->enumClass),
            $instance instanceof Confirmed => $propRules[] = 'confirmed',
            $instance instanceof Different => $propRules[] = 'different:'.$instance->field,
            $instance instanceof Same => $propRules[] = 'same:'.$instance->field,
            $instance instanceof Between => $propRules[] = 'between:'.$instance->min.','.$instance->max,
            $instance instanceof ArrayRule => self::applyArrayRule($instance, $propRules),
            $instance instanceof Prohibited => $propRules[] = 'prohibited',
            $instance instanceof Present => $propRules[] = 'present',
            $instance instanceof Declined => $propRules[] = 'declined',
            $instance instanceof Accepted => $propRules[] = 'accepted',
            $instance instanceof StartsWith => $propRules[] = 'starts_with:'.implode(',', (array) $instance->prefix),
            $instance instanceof EndsWith => $propRules[] = 'ends_with:'.implode(',', (array) $instance->suffix),
            $instance instanceof Nullable => $propRules[] = 'nullable',
            $instance instanceof Sometimes => $propRules[] = 'sometimes',
            $instance instanceof Distinct => $propRules[] = 'distinct',
            $instance instanceof Size => $propRules[] = 'size:'.$instance->value,
            $instance instanceof JsonAttribute => $propRules[] = 'json',
            $instance instanceof RequiredIf => self::applyRequiredIfRule($instance, $propRules),
            $instance instanceof RequiredUnless => self::applyRequiredUnlessRule($instance, $propRules),
            $instance instanceof RequiredWith => $propRules[] = 'required_with:'.implode(',', $instance->fields),
            $instance instanceof RequiredWithAll => $propRules[] = 'required_with_all:'.implode(',', $instance->fields),
            $instance instanceof RequiredWithout => $propRules[] = 'required_without:'.implode(',', $instance->fields),
            $instance instanceof RequiredWithoutAll => $propRules[] = 'required_without_all:'.implode(',', $instance->fields),
            default => null,
        };
    }

    /**
     * @param  list<string|EnumRule>  $propRules
     */
    private static function applyRequiredIfRule(RequiredIf $instance, array &$propRules): void
    {
        $values = (array) $instance->value;
        $propRules[] = 'required_if:'.$instance->field.','.implode(',', array_map(
            fn (mixed $v): string => is_string($v) ? $v : (is_scalar($v) ? (string) $v : ''),
            $values,
        ));
    }

    /**
     * @param  list<string|EnumRule>  $propRules
     */
    private static function applyRequiredUnlessRule(RequiredUnless $instance, array &$propRules): void
    {
        $values = (array) $instance->value;
        $propRules[] = 'required_unless:'.$instance->field.','.implode(',', array_map(
            fn (mixed $v): string => is_string($v) ? $v : (is_scalar($v) ? (string) $v : ''),
            $values,
        ));
    }

    /**
     * Apply array validation rule with optional min/max count.
     *
     * @param  list<string|EnumRule>  $propRules
     */
    private static function applyArrayRule(ArrayRule $instance, array &$propRules): void
    {
        if ($instance->min !== null && $instance->max !== null) {
            // Use Laravel's between rule for array count bounds
            $propRules[] = 'array';
            $propRules[] = 'min:'.$instance->min;
            $propRules[] = 'max:'.$instance->max;
        } elseif ($instance->min !== null) {
            $propRules[] = 'array';
            $propRules[] = 'min:'.$instance->min;
        } elseif ($instance->max !== null) {
            $propRules[] = 'array';
            $propRules[] = 'max:'.$instance->max;
        } else {
            $propRules[] = 'array';
        }
    }

    /**
     * @param  array<string, mixed>  $propMeta
     */
    private static function applyMetaAttribute(object $instance, array &$propMeta): void
    {
        if ($instance instanceof CastAttribute) {
            $propMeta['cast'] = $instance->type;
        }

        if ($instance instanceof MapFrom) {
            $propMeta['map_from'] = $instance->key;
        }

        if ($instance instanceof Hidden) {
            $propMeta['hidden'] = true;
        }

        if ($instance instanceof NestedArray) {
            $propMeta['nested_array_class'] = $instance->dtoClass;
        }

        if ($instance instanceof Collection) {
            $propMeta['collection_class'] = $instance->dtoClass;
        }
    }

    /**
     * Collect custom validation message from attribute instance.
     *
     * Uses each attribute's ruleKey() method for message key generation,
     * ensuring keys always match Laravel's rule names (#9).
     *
     * @param  array<string, string>  $messages
     */
    private static function collectMessage(object $instance, array &$messages, string $name): void
    {
        if ($instance instanceof ValidationAttribute && property_exists($instance, 'message') && $instance->message !== null) {
            $messages["{$name}.{$instance->ruleKey()}"] = (string) $instance->message;
        }
    }

    /**
     * Detect if the property type is a ValueObject class.
     *
     * Returns the FQCN of the VO class if detected, null otherwise.
     *
     * @return class-string<ValueObjectContract>|null
     */
    private static function detectValueObjectClass(?\ReflectionType $type): ?string
    {
        if ($type instanceof \ReflectionNamedType) {
            return self::checkValueObject($type->getName());
        }

        if ($type instanceof \ReflectionUnionType) {
            foreach ($type->getTypes() as $innerType) {
                if ($innerType instanceof \ReflectionNamedType) {
                    $result = self::checkValueObject($innerType->getName());
                    if ($result !== null) {
                        return $result;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Check if a class name implements ValueObject contract.
     *
     * @return class-string<ValueObjectContract>|null
     */
    private static function checkValueObject(string $typeName): ?string
    {
        // Skip PHP scalar/builtin types
        if (in_array($typeName, ['int', 'float', 'string', 'bool', 'array', 'mixed', 'object', 'callable', 'iterable', 'null', 'void', 'never', 'self', 'static', 'parent'], true)) {
            return null;
        }

        if (! class_exists($typeName) && ! interface_exists($typeName)) {
            return null;
        }

        if (in_array(ValueObjectContract::class, class_implements($typeName) ?: [], true)) {
            /** @var class-string<ValueObjectContract> $typeName */
            return $typeName;
        }

        return null;
    }

    /**
     * Detect if the property type is a BackedEnum.
     *
     * Returns the FQCN of the enum class if detected, null otherwise.
     *
     * @return class-string<\BackedEnum>|null
     */
    private static function detectEnumClass(?\ReflectionType $type): ?string
    {
        if ($type instanceof \ReflectionNamedType) {
            return self::checkEnumClass($type->getName());
        }

        if ($type instanceof \ReflectionUnionType) {
            foreach ($type->getTypes() as $innerType) {
                if ($innerType instanceof \ReflectionNamedType) {
                    $result = self::checkEnumClass($innerType->getName());
                    if ($result !== null) {
                        return $result;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Check if a class name is a BackedEnum.
     *
     * @return class-string<\BackedEnum>|null
     */
    private static function checkEnumClass(string $typeName): ?string
    {
        if (in_array($typeName, ['int', 'float', 'string', 'bool', 'array', 'mixed', 'object', 'callable', 'iterable', 'null', 'void', 'never', 'self', 'static', 'parent'], true)) {
            return null;
        }

        if (! enum_exists($typeName)) {
            return null;
        }

        if (! is_a($typeName, \BackedEnum::class, true)) {
            return null;
        }

        /** @var class-string<\BackedEnum> $typeName */
        return $typeName;
    }

    /**
     * Detect if the property type is a nested DTO class.
     *
     * Returns the FQCN of the DTO subclass if detected, null otherwise.
     * A DTO is detected by checking if the class extends DataTransferObject.
     *
     * @return class-string<DataTransferObject>|null
     */
    private static function detectDtoClass(?\ReflectionType $type): ?string
    {
        if ($type instanceof \ReflectionNamedType) {
            return self::checkDtoClass($type->getName());
        }

        if ($type instanceof \ReflectionUnionType) {
            foreach ($type->getTypes() as $innerType) {
                if ($innerType instanceof \ReflectionNamedType) {
                    $result = self::checkDtoClass($innerType->getName());
                    if ($result !== null) {
                        return $result;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Check if a class name is a DTO subclass.
     *
     * @return class-string<DataTransferObject>|null
     */
    private static function checkDtoClass(string $typeName): ?string
    {
        if (in_array($typeName, ['int', 'float', 'string', 'bool', 'array', 'mixed', 'object', 'callable', 'iterable', 'null', 'void', 'never', 'self', 'static', 'parent'], true)) {
            return null;
        }

        if (! class_exists($typeName)) {
            return null;
        }

        if (is_subclass_of($typeName, DataTransferObject::class)) {
            return $typeName;
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private static function inferBaseRules(?\ReflectionType $type, bool $nullable, bool $hasDefault): array
    {
        $rules = [];

        if ($nullable && ! $hasDefault) {
            $rules[] = 'sometimes';
        }

        if ($type instanceof \ReflectionNamedType) {
            $rules = array_merge($rules, self::rulesForNamedType($type->getName()));
        } elseif ($type instanceof \ReflectionUnionType) {
            // For union types (e.g., int|float|string), collect rules from each member type
            // but avoid redundant rules (e.g., int implies numeric, no need for both 'integer' and 'numeric')
            $unionRules = [];
            foreach ($type->getTypes() as $innerType) {
                if ($innerType instanceof \ReflectionNamedType && ! $innerType->allowsNull()) {
                    foreach (self::rulesForNamedType($innerType->getName()) as $rule) {
                        $unionRules[$rule] = true;
                    }
                }
            }
            $rules = array_merge($rules, array_keys($unionRules));
        }

        return $rules;
    }

    /**
     * Get validation rules for a single named PHP type.
     *
     * @return list<string>
     */
    private static function rulesForNamedType(string $typeName): array
    {
        return match ($typeName) {
            'int' => ['integer'],
            'float' => ['numeric'],
            default => [],
        };
    }
}
