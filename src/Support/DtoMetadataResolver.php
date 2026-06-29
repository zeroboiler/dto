<?php
/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Support;

use ReflectionClass;
use ReflectionProperty;
use ZeroBoiler\DTO\Attributes\Boolean;
use ZeroBoiler\DTO\Attributes\Cast as CastAttribute;
use ZeroBoiler\DTO\Attributes\Date as DateAttribute;
use ZeroBoiler\DTO\Attributes\DefaultValue as DefaultValueAttribute;
use ZeroBoiler\DTO\Attributes\Email;
use ZeroBoiler\DTO\Attributes\Enum as EnumAttribute;
use ZeroBoiler\DTO\Attributes\Hidden;
use ZeroBoiler\DTO\Attributes\In;
use ZeroBoiler\DTO\Attributes\Integer;
use ZeroBoiler\DTO\Attributes\MapFrom;
use ZeroBoiler\DTO\Attributes\Max;
use ZeroBoiler\DTO\Attributes\Min;
use ZeroBoiler\DTO\Attributes\Numeric;
use ZeroBoiler\DTO\Attributes\Pattern;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\Attributes\Url;
use ZeroBoiler\DTO\Attributes\Uuid;

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
     *     properties: array<string, array{
     *         map_from: ?string,
     *         default: mixed,
     *         cast: ?string,
     *         hidden: bool,
     *         has_default: bool,
     *         nullable: bool
     *     }>,
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
        $messages = [];

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
            ];

            if ($propMeta['has_default']) {
                $propMeta['default'] = $param->getDefaultValue();
            }

            $propRules = self::inferBaseRules($type, $propMeta['nullable'], $propMeta['has_default']);

            self::resolveAttributes($propReflection, $propMeta, $propRules, $messages, $name);

            $properties[$name] = $propMeta;
            if ($propRules !== []) {
                $rules[$name] = array_unique($propRules);
            }
        }

        return ['properties' => $properties, 'rules' => $rules, 'messages' => $messages];
    }

    /**
     * @param  list<string>  $propRules
     * @param  array<string, string>  $messages
     */
    private static function resolveAttributes(
        ReflectionProperty $propReflection,
        array &$propMeta,
        array &$propRules,
        array &$messages,
        string $name,
    ): void {
        foreach ($propReflection->getAttributes() as $attr) {
            $instance = $attr->newInstance();

            self::applyValidationAttribute($instance, $propRules);
            self::applyMetaAttribute($instance, $propMeta);

            if ($instance instanceof DefaultValueAttribute) {
                $propMeta['default'] = $instance->value;
                $propMeta['has_default'] = true;
            }

            self::collectMessage($instance, $messages, $name);
        }
    }

    /**
     * @param  list<string>  $propRules
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
            $instance instanceof EnumAttribute => $propRules[] = 'in:'.implode(
                ',',
                array_map(fn (\BackedEnum $c): int|string => $c->value, $instance->enumClass::cases())
            ),
            default => null,
        };
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
    }

    /**
     * @param  array<string, string>  $messages
     */
    private static function collectMessage(object $instance, array &$messages, string $name): void
    {
        if (property_exists($instance, 'message') && $instance->message !== null) {
            $attrClass = new ReflectionClass($instance)->getShortName();
            $ruleKey = strtolower((string) preg_replace('/([a-z])([A-Z])/', '$1_$2', $attrClass));
            $messages["{$name}.{$ruleKey}"] = $instance->message;
        }
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
            $typeName = $type->getName();
            if ($typeName === 'int') {
                $rules[] = 'integer';
            } elseif ($typeName === 'float') {
                $rules[] = 'numeric';
            }
        }

        return $rules;
    }
}
