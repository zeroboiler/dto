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
 * Resolves validation rules and property metadata from DTO attribute definitions.
 *
 * @internal Not part of the public API — used internally by {@see DataTransferObject}.
 *          Consumers should use {@see DataTransferObject::rules()} or
 *          {@see DataTransferObject::fromArray()} instead of calling this class directly.
 *
 * Reads constructor parameters via reflection, detects attribute types
 * (ValueObject, BackedEnum, nested DTO), infers base validation rules
 * from PHP types, and collects validation attribute rules.
 *
 * Results are cached by the caller ({@see DataTransferObject::resolveMetadata()})
 * with TTL-based invalidation in dev environments.
 *
 * @phpstan-type DtoPropertyMeta array{
 *     map_from: string|null,
 *     default: mixed,
 *     has_default: bool,
 *     cast: string|null,
 *     hidden: bool,
 *     nullable: bool,
 *     value_object_class: class-string<ValueObjectContract>|null,
 *     dto_class: class-string<DataTransferObject>|null,
 *     enum_class: class-string<\BackedEnum>|null,
 *     nested_array_class: class-string<DataTransferObject>|null,
 *     collection_class: class-string<DataTransferObject>|null
 * }
 *
 * @phpstan-type DtoResolvedMetadata array{
 *     properties: array<string, DtoPropertyMeta>,
 *     rules: array<string, array<int, mixed>>,
 *     messages: array<string, string>
 * }
 *
 * @see \ZeroBoiler\DTO\DataTransferObject For the base class that calls this resolver
 * @see \ZeroBoiler\DTO\Contracts\ValidationAttribute For the validation attribute contract
 */
final class DtoMetadataResolver
{
    /**
     * PHP builtin types that are never classes/interfaces/enums.
     *
     * @var list<string>
     */
    private const BUILTIN_TYPES = [
        'int', 'float', 'string', 'bool', 'array', 'mixed', 'object',
        'callable', 'iterable', 'null', 'void', 'never', 'self', 'static', 'parent',
    ];

    /**
     * Check if a type name is a PHP builtin.
     */
    private static function isBuiltinType(string $typeName): bool
    {
        return in_array($typeName, self::BUILTIN_TYPES, true);
    }

    /**
     * Resolve all metadata for a DTO class.
     *
     * Reads constructor parameters via reflection, detects attribute types
     * (ValueObject, BackedEnum, nested DTO), infers base validation rules
     * from PHP types, and collects validation attribute rules.
     *
     * The returned metadata structure:
     * - `properties`: per-property metadata (map_from, cast, hidden, nullable, etc.)
     * - `rules`: Laravel validation rules per property
     * - `messages`: custom validation messages keyed as `"{field}.{rule}"`
     *
     * @param  class-string  $class  The DTO class to resolve metadata for
     * @return DtoResolvedMetadata Associative array with properties, rules, and messages
     *
     * @throws \ReflectionException If the class does not exist
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
                $rules[$name] = self::deduplicateRules($propRules);
            }
        }

        // Merge wildcard rules (e.g., tags.* => ['distinct'])
        foreach ($extraRules as $field => $fieldRules) {
            $rules[$field] = $fieldRules;
        }

        return ['properties' => $properties, 'rules' => $rules, 'messages' => $messages];
    }

    /**
     * Resolve all attributes on a property into metadata and validation rules.
     *
     * Iterates over all reflection attributes on the given property and:
     * - Applies validation rules from {@see ValidationAttribute} implementations
     * - Sets metadata flags (hidden, map_from, cast, nested_array_class, collection_class)
     * - Sets default values from {@see DefaultValue} attributes
     * - Collects custom validation messages keyed by rule name
     * - Registers wildcard rules for composite attributes (e.g., Distinct → `field.*`)
     *
     * @param  ReflectionProperty  $propReflection  The property to inspect
     * @param  array<string, mixed>  $propMeta  Property metadata (modified by reference)
     * @param  list<string|\Illuminate\Validation\Rules\Enum>  $propRules  Accumulated validation rules (modified by reference)
     * @param  array<string, string>  $messages  Custom validation messages (modified by reference)
     * @param  array<string, array<int, mixed>>  $extraRules  Wildcard rules for array elements (modified by reference)
     * @param  string  $name  Property name for rule key generation
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
     * @param  list<string|\Illuminate\Validation\Rules\Enum>  $propRules
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
     * @param  list<string|\Illuminate\Validation\Rules\Enum>  $propRules
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
     * @param  list<string|\Illuminate\Validation\Rules\Enum>  $propRules
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
     * @param  list<string|\Illuminate\Validation\Rules\Enum>  $propRules
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
     * Only attributes implementing {@see ValidationAttribute} with a non-null
     * `message` property contribute messages. The `message` property is accessed
     * via reflection to avoid PHPStan level 9 warnings on dynamic property access.
     *
     * @param  array<string, string>  $messages
     */
    private static function collectMessage(object $instance, array &$messages, string $name): void
    {
        if (! ($instance instanceof ValidationAttribute)) {
            return;
        }

        $ref = new \ReflectionProperty($instance, 'message');
        $messageValue = $ref->getValue($instance);

        if ($messageValue !== null) {
            $messages["{$name}.{$instance->ruleKey()}"] = (string) $messageValue;
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
        if (self::isBuiltinType($typeName)) {
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
        if (self::isBuiltinType($typeName)) {
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
        if (self::isBuiltinType($typeName)) {
            return null;
        }

        if (! class_exists($typeName)) {
            return null;
        }

        if (is_subclass_of($typeName, DataTransferObject::class)) {
            /** @var class-string<DataTransferObject> $typeName */
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

    /**
     * Deduplicate validation rules, handling both string rules and EnumRule objects.
     *
     * Unlike array_unique(), which compares objects by identity (always unique),
     * this method compares EnumRule instances by their target enum class and
     * string rules by value — ensuring true deduplication.
     *
     * @param  list<string|\Illuminate\Validation\Rules\Enum>  $rules
     * @return list<string|\Illuminate\Validation\Rules\Enum>
     */
    private static function deduplicateRules(array $rules): array
    {
        /** @var array<string|\Illuminate\Validation\Rules\Enum> $seen */
        $seen = [];
        /** @var list<string|\Illuminate\Validation\Rules\Enum> $deduped */
        $deduped = [];

        foreach ($rules as $rule) {
            $key = is_string($rule) ? $rule : self::enumRuleKey($rule);
            if (! array_key_exists($key, $seen)) {
                $seen[$key] = $rule;
                $deduped[] = $rule;
            }
        }

        return $deduped;
    }

    /**
     * Generate a unique key for an EnumRule instance for deduplication.
     *
     * Uses reflection to extract the enum class name so that two EnumRule
     * instances targeting the same enum are correctly deduplicated,
     * preventing redundant identical rules within a single property's rule set.
     */
    private static function enumRuleKey(\Illuminate\Validation\Rules\Enum $rule): string
    {
        $ref = new \ReflectionProperty($rule, 'rule');
        /** @var string|class-string<\BackedEnum> $enumClass */
        $enumClass = $ref->getValue($rule);

        return 'enum:'.(is_string($enumClass) ? $enumClass : spl_object_id($rule));
    }
}
