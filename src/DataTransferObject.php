<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use JsonSerializable;
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
 * Base Data Transfer Object — zero boilerplate hydration, validation and serialization.
 *
 * Usage:
 *
 *   class CreateUserDTO extends DataTransferObject
 *   {
 *       public function __construct(
 *           #[Required, Email]
 *           public readonly string $email,
 *
 *           #[Required, Min(2), Max(50)]
 *           public readonly string $name,
 *
 *           #[DefaultValue('active')]
 *           public readonly string $status,
 *
 *           #[Cast('array')]
 *           public readonly array $tags = [],
 *       ) {}
 *   }
 *
 *   $dto = CreateUserDTO::fromRequest($request);
 *   $dto = CreateUserDTO::fromArray($data);
 *   $array = $dto->toArray();
 *   $json = $dto->toJson();
 *
 * @implements Arrayable<string, mixed>
 *
 * @phpstan-consistent-constructor
 */
abstract class DataTransferObject implements Arrayable, JsonSerializable
{
    /** @var array<string, array<string, mixed>> Cache for reflection metadata per class */
    private static array $_metadataCache = [];

    /**
     * Flush the metadata cache for a specific class or all classes.
     * Useful in long-running processes (Octane, Swoole) and tests.
     */
    public static function flushMetadataCache(?string $class = null): void
    {
        if ($class !== null) {
            unset(self::$_metadataCache[$class]);
        } else {
            self::$_metadataCache = [];
        }
    }

    /**
     * Create DTO instance from an associative array.
     *
     * @param  array<string, mixed>  $data
     * @param  bool  $validate  Run validation before hydration
     *
     * @throws ValidationException
     */
    public static function fromArray(array $data, bool $validate = true): static
    {
        if ($validate) {
            static::validateArray($data);
        }

        $metadata = self::resolveMetadata();
        $args = [];

        foreach ($metadata['properties'] as $name => $prop) {
            $sourceKey = $prop['map_from'] ?? $name;
            $value = Arr::get($data, $sourceKey);

            // Apply default if missing or empty (null, empty string)
            if (($value === null || $value === '') && array_key_exists('default', $prop)) {
                $value = $prop['default'];
            }

            // Apply casting
            if ($prop['cast'] !== null && $value !== null) {
                $value = self::castValue($value, $prop['cast']);
            }

            $args[$name] = $value;
        }

        return new static(...$args);
    }

    /**
     * Create DTO from an HTTP request.
     *
     * Uses request input (merges route params, query and body).
     *
     * @throws ValidationException
     */
    public static function fromRequest(Request $request, bool $validate = true): static
    {
        return static::fromArray($request->all(), $validate);
    }

    /**
     * Validate an array of data against DTO rules.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed> Validated data
     *
     * @throws ValidationException
     */
    public static function validateArray(array $data): array
    {
        $metadata = self::resolveMetadata();

        /** @var ValidatorContract $validator */
        $validator = Validator::make($data, $metadata['rules'], $metadata['messages']);

        return $validator->validate();
    }

    /**
     * Get validation rules derived from attributes.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return self::resolveMetadata()['rules'];
    }

    /**
     * Convert DTO to associative array, including hidden properties.
     *
     * @return array<string, mixed>
     */
    public function allValues(): array
    {
        $metadata = self::resolveMetadata();
        $result = [];

        foreach ($metadata['properties'] as $name => $prop) {
            $value = $this->{$name};

            // Convert nested DTOs
            if ($value instanceof DataTransferObject) {
                $value = $value->allValues();
            } elseif ($value instanceof \BackedEnum) {
                $value = $value->value;
            } elseif ($value instanceof \UnitEnum) {
                $value = $value->name;
            } elseif ($value instanceof \DateTimeInterface) {
                $value = $value->format(\DateTimeInterface::ATOM);
            }

            $result[$name] = $value;
        }

        return $result;
    }

    /**
     * Convert DTO to associative array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $metadata = self::resolveMetadata();
        $result = [];

        foreach ($metadata['properties'] as $name => $prop) {
            // Skip hidden properties
            if ($prop['hidden'] === true) {
                continue;
            }

            $value = $this->{$name};

            // Convert nested DTOs
            if ($value instanceof DataTransferObject) {
                $value = $value->toArray();
            } elseif ($value instanceof \BackedEnum) {
                $value = $value->value;
            } elseif ($value instanceof \UnitEnum) {
                $value = $value->name;
            } elseif ($value instanceof \DateTimeInterface) {
                $value = $value->format(\DateTimeInterface::ATOM);
            }

            $result[$name] = $value;
        }

        return $result;
    }

    /**
     * Convert DTO to JSON string.
     */
    public function toJson(int $options = 0): string
    {
        return json_encode($this->jsonSerialize(), $options);
    }

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }

    /**
     * Check equality with another DTO of the same type.
     */
    public function equals(self $other): bool
    {
        return $this->toArray() === $other->toArray();
    }

    /**
     * Create a new instance with modified values (immutable update).
     *
     * @param  array<string, mixed>  $overrides
     */
    public function with(array $overrides, bool $validate = true): static
    {
        // Use all property values (including hidden) to avoid data loss
        $data = $this->allValues();
        $data = array_merge($data, $overrides);

        return static::fromArray($data, validate: $validate);
    }

    /**
     * Resolve all metadata for this DTO class from attributes.
     * Cached per class.
     *
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
    private static function resolveMetadata(): array
    {
        $class = static::class;

        if (isset(self::$_metadataCache[$class])) {
            return self::$_metadataCache[$class];
        }

        $reflection = new ReflectionClass($class);
        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            return self::$_metadataCache[$class] = [
                'properties' => [],
                'rules' => [],
                'messages' => [],
            ];
        }

        $properties = [];
        $rules = [];
        $messages = [];

        foreach ($constructor->getParameters() as $param) {
            $name = $param->getName();
            $type = $param->getType();

            // Check if property exists on class
            if (! $reflection->hasProperty($name)) {
                continue;
            }

            $propReflection = new ReflectionProperty($class, $name);
            $propMeta = [
                'map_from' => null,
                'default' => null,
                'has_default' => $param->isDefaultValueAvailable(),
                'cast' => null,
                'hidden' => false,
                'nullable' => $type?->allowsNull() ?? true,
            ];

            // Use default value from constructor parameter
            if ($propMeta['has_default']) {
                $propMeta['default'] = $param->getDefaultValue();
            }

            $propRules = [];
            $propMessages = [];

            // If nullable and not required, add "sometimes" or "nullable"
            if ($propMeta['nullable'] && ! $param->isDefaultValueAvailable()) {
                $propRules[] = 'sometimes';
            }

            // Parse property attributes
            foreach ($propReflection->getAttributes() as $attr) {
                $instance = $attr->newInstance();

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
                    $instance instanceof CastAttribute => $propMeta['cast'] = $instance->type,
                    $instance instanceof MapFrom => $propMeta['map_from'] = $instance->key,
                    $instance instanceof Hidden => $propMeta['hidden'] = true,
                    $instance instanceof DefaultValueAttribute => $propMeta['default'] = $instance->value,
                    default => null,
                };

                // Collect custom messages
                if (property_exists($instance, 'message') && $instance->message !== null) {
                    $attrClass = new ReflectionClass($instance)->getShortName();
                    $ruleKey = strtolower((string) preg_replace('/([a-z])([A-Z])/', '$1_$2', $attrClass));
                    $messages["{$name}.{$ruleKey}"] = $instance->message;
                }
            }

            // Infer type rule from PHP type
            if ($type instanceof \ReflectionNamedType) {
                $typeName = $type->getName();
                if (in_array($typeName, ['int', 'float', 'string', 'bool', 'array'], true)) {
                    if (! in_array('integer', $propRules) && $typeName === 'int') {
                        $propRules[] = 'integer';
                    } elseif (! in_array('numeric', $propRules) && $typeName === 'float') {
                        $propRules[] = 'numeric';
                    }
                }
            }

            $properties[$name] = $propMeta;
            if ($propRules !== []) {
                $rules[$name] = array_unique($propRules);
            }
        }

        return self::$_metadataCache[$class] = [
            'properties' => $properties,
            'rules' => $rules,
            'messages' => $messages,
        ];
    }

    /**
     * Cast a value to the specified type.
     */
    private static function castValue(mixed $value, string $type): mixed
    {
        return match ($type) {
            'int', 'integer' => (int) $value,
            'float', 'double' => (float) $value,
            'string' => (string) $value,
            'bool', 'boolean' => filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false,
            'array' => is_array($value) ? $value : ((string) $value !== '' ? json_decode((string) $value, true) : []),
            default => $value,
        };
    }
}
