<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Console\Commands;

use Illuminate\Console\Command;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionUnionType;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\ValueObjects\Contracts\ValueObject as ValueObjectContract;

/**
 * Generate Pest tests for a DTO class.
 *
 * Reads the DTO's constructor parameters via reflection to generate
 * type-appropriate fake data, and outputs a complete Pest test file
 * with rules, fromArray, toArray, toJson, equals, isEmpty, only,
 * except, with, fromJson, and fromPartialArray tests.
 *
 *   php artisan zeroboiler:dto-test "App\DTO\CreateUserDTO"
 *
 * @see \ZeroBoiler\DTO\Console\Commands\MakeDtoSchemaCommand For schema generation
 */
final class MakeDtoTestCommand extends Command
{
    protected string $signature = 'zeroboiler:dto-test {class : The DTO class FQN} {--dir= : Output directory}';

    protected string $description = 'Generate Pest tests for a ZeroBoiler DTO class';

    #[\Override]
    public function handle(): int
    {
        /** @var string $dtoClass */
        $dtoClass = $this->argument('class');

        if (! class_exists($dtoClass)) {
            $this->error("DTO class '{$dtoClass}' not found.");

            return self::FAILURE;
        }

        $shortName = class_basename($dtoClass);
        $defaultDir = \function_exists('base_path') ? base_path('tests/Unit/DTO') : getcwd().'/tests/Unit/DTO';
        /** @var string|null $optDir */
        $optDir = $this->option('dir');
        $dir = $optDir ?? $defaultDir;
        $path = rtrim($dir, '/')."/{$shortName}Test.php";

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        if (file_exists($path) && ! $this->confirm("File {$path} already exists. Overwrite?")) {
            $this->info('Cancelled.');

            return self::SUCCESS;
        }

        $fakeData = $this->generateFakeData($dtoClass);
        $fakeDataStr = $this->formatDataAsPhp($fakeData);

        $rules = $dtoClass::rules();
        $ruleStrings = [];
        foreach ($rules as $field => $fieldRules) {
            $formattedRules = array_map(static function (mixed $r): string {
                if ($r instanceof \Illuminate\Validation\Rules\Enum) {
                    $ref = new \ReflectionProperty($r, 'rule');
                    $enumClass = $ref->getValue($r);

                    return "'enum:'.{$enumClass}::class";
                }

                if (is_object($r) && method_exists($r, '__toString')) {
                    return (string) $r;
                }

                return is_string($r) ? "'{$r}'" : "'".(string) $r."'";
            }, $fieldRules);
            $ruleStrings[] = "    '{$field}' => [".implode(', ', $formattedRules).'],';
        }
        $rulesStr = implode("\n", $ruleStrings);

        // Extract field names for only/except tests
        $fieldNames = array_keys($fakeData);
        $firstField = $fieldNames[0] ?? 'id';
        $secondField = $fieldNames[1] ?? $firstField;
        $partialData = $this->formatDataAsPhp(array_slice($fakeData, 0, (int) ceil(count($fakeData) / 2), true));

        $content = <<<PHP
<?php

declare(strict_types=1);

use {$dtoClass};

describe('{$shortName}', function () {
    it('has validation rules', function () {
        \\\$rules = {$shortName}::rules();

        expect(\\\$rules)->toBeArray();
        expect(\\\$rules)->not->toBeEmpty();
    });

    it('can be created from array', function () {
        \\\$data = {$fakeDataStr};
        \\\$dto = {$shortName}::fromArray(\\\$data, validate: false);

        expect(\\\$dto)->toBeInstanceOf({$shortName}::class);
    });

    it('can be converted to array', function () {
        \\\$data = {$fakeDataStr};
        \\\$dto = {$shortName}::fromArray(\\\$data, validate: false);

        expect(\\\$dto->toArray())->toBeArray();
    });

    it('can be serialized to JSON', function () {
        \\\$data = {$fakeDataStr};
        \\\$dto = {$shortName}::fromArray(\\\$data, validate: false);

        expect(\\\$dto->toJson())->toBeJson();
    });

    it('round-trips through JSON serialization', function () {
        \\\$data = {$fakeDataStr};
        \\\$dto = {$shortName}::fromArray(\\\$data, validate: false);
        \\\$json = \\\$dto->toJson();
        \\\$restored = {$shortName}::fromJson(\\\$json, validate: false);

        expect(\\\$restored)->toBeInstanceOf({$shortName}::class);
    });

    it('supports equals() value comparison', function () {
        \\\$data = {$fakeDataStr};
        \\\$dto1 = {$shortName}::fromArray(\\\$data, validate: false);
        \\\$dto2 = {$shortName}::fromArray(\\\$data, validate: false);

        expect(\\\$dto1->equals(\\\$dto2))->toBeTrue();
    });

    it('supports only() field filtering', function () {
        \\\$data = {$fakeDataStr};
        \\\$dto = {$shortName}::fromArray(\\\$data, validate: false);
        \\\$filtered = \\\$dto->only('{$firstField}');

        expect(\\\$filtered)->toBeArray();
        expect(\\\$filtered)->toHaveKey('{$firstField}');
    });

    it('supports except() field exclusion', function () {
        \\\$data = {$fakeDataStr};
        \\\$dto = {$shortName}::fromArray(\\\$data, validate: false);
        \\\$filtered = \\\$dto->except('{$firstField}');

        expect(\\\$filtered)->toBeArray();
        expect(\\\$filtered)->not->toHaveKey('{$firstField}');
    });

    it('supports with() immutable override', function () {
        \\\$data = {$fakeDataStr};
        \\\$dto = {$shortName}::fromArray(\\\$data, validate: false);
        \\\$modified = \\\$dto->with(['{$firstField}' => \\\$data['{$firstField}']]);

        expect(\\\$modified)->toBeInstanceOf({$shortName}::class);
        expect(\\\$modified)->not->toBe(\\\$dto);
    });

    it('supports fromPartialArray for partial updates', function () {
        \\\$partial = {$partialData};
        \\\$dto = {$shortName}::fromPartialArray(\\\$partial, validate: false);

        expect(\\\$dto)->toBeInstanceOf({$shortName}::class);
    });

    it('rules match expected structure', function () {
        \\\$rules = {$shortName}::rules();

        \\\$expected = [
{$rulesStr}
        ];

        foreach (\\\$expected as \\\$field => \\\$fieldRules) {
            expect(\\\$rules)->toHaveKey(\\\$field);
        }
    });
});
PHP;

        file_put_contents($path, $content);
        $basePath = \function_exists('base_path') ? base_path().'/' : getcwd().'/';
        $relative = str_replace($basePath, '', $path);
        $this->info("Generated: {$relative}");

        return self::SUCCESS;
    }

    /**
     * Generate fake data for a DTO class by reading its properties via reflection.
     *
     * Generates type-appropriate fake values for each required constructor parameter.
     * For complex types (nested DTOs without defaults, DtoCollection), the parameter
     * is skipped since a meaningful fake value cannot be generated.
     *
     * @param  class-string  $dtoClass
     * @return array<string, mixed>
     */
    private function generateFakeData(string $dtoClass): array
    {
        $reflection = new ReflectionClass($dtoClass);
        $constructor = $reflection->getConstructor();

        if ($constructor === null) {
            return [];
        }

        $data = [];

        foreach ($constructor->getParameters() as $param) {
            $name = $param->getName();
            $type = $param->getType();

            // Skip optional parameters that have a default — they don't need fake data
            if ($param->isDefaultValueAvailable()) {
                continue;
            }

            if ($type === null) {
                continue;
            }

            // Skip nested DTO properties — cannot generate meaningful fake nested structure
            if ($this->isNestedDtoType($type)) {
                continue;
            }

            // Skip DtoCollection properties — requires typed array of DTO instances
            if ($this->isDtoCollectionType($type)) {
                continue;
            }

            $fake = $this->fakeValueForType($type, $name);

            // Skip null values — they won't satisfy required properties
            if ($fake !== null) {
                $data[$name] = $fake;
            }
        }

        return $data;
    }

    /**
     * Check if a reflection type represents a nested DTO class.
     *
     * Checks both direct named types and nullable wrappers.
     *
     * @param  \ReflectionType  $type  The property reflection type to inspect
     * @return bool True if the type is or contains a DataTransferObject subclass
     */
    private function isNestedDtoType(\ReflectionType $type): bool
    {
        if ($type instanceof ReflectionNamedType) {
            $typeName = $type->getName();
            if (class_exists($typeName) && is_subclass_of($typeName, DataTransferObject::class)) {
                return true;
            }
        }

        if ($type instanceof ReflectionUnionType) {
            foreach ($type->getTypes() as $innerType) {
                if ($innerType instanceof ReflectionNamedType
                    && $innerType->getName() !== 'null'
                    && $this->isNestedDtoType($innerType)
                ) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Check if a reflection type represents a DtoCollection class.
     *
     * @param  \ReflectionType  $type  The property reflection type to inspect
     * @return bool True if the type is or extends DtoCollection
     */
    private function isDtoCollectionType(\ReflectionType $type): bool
    {
        if ($type instanceof ReflectionNamedType) {
            $typeName = $type->getName();

            return $typeName === \ZeroBoiler\DTO\DtoCollection::class
                || (class_exists($typeName) && is_subclass_of($typeName, \ZeroBoiler\DTO\DtoCollection::class));
        }

        return false;
    }

    /**
     * Generate a type-appropriate fake value for a property.
     *
     * Uses the property name to generate more realistic values
     * (e.g. "email" fields get fake email addresses).
     *
     * Handles scalar types, BackedEnum, ValueObject, and nested DTO types.
     * For complex types that cannot be faked from reflection alone (union types,
     * unknown classes), returns null and the parameter is skipped.
     */
    private function fakeValueForType(\ReflectionType $type, string $name): mixed
    {
        $lowerName = strtolower($name);

        // Name-based hints first (only for scalar-compatible types)
        if ($type instanceof ReflectionNamedType) {
            $typeName = $type->getName();

            // Skip complex types — let the type-based handler below deal with them
            if ($typeName === 'array' || $typeName === 'bool' || $typeName === 'boolean') {
                // fall through to name-based hints
            } elseif ($typeName === 'string' || $typeName === 'int' || $typeName === 'float') {
                // fall through to name-based hints
            } else {
                // Complex type (enum, VO, DTO, Carbon, etc.) — skip name hints
                return $this->fakeValueForComplexType($type, $name);
            }
        }

        // Name-based hints for scalar types
        if (str_contains($lowerName, 'email')) {
            return 'test@example.com';
        }
        if (str_contains($lowerName, 'url') || str_contains($lowerName, 'link')) {
            return 'https://example.com';
        }
        if (str_contains($lowerName, 'name')) {
            return 'Test '.ucfirst($name);
        }
        if (str_contains($lowerName, 'uuid')) {
            return '550e8400-e29b-41d4-a716-446655440000';
        }
        if (str_contains($lowerName, 'date') || str_contains($lowerName, 'time')) {
            return '2024-01-15 10:30:00';
        }
        if (str_contains($lowerName, 'phone')) {
            return '+123****7890';
        }
        if (str_contains($lowerName, 'password')) {
            return 'password123';
        }

        // Type-based fallback
        if ($type instanceof ReflectionNamedType) {
            return $this->fakeValueForComplexType($type, $name);
        }

        // Union types — try to find a non-null scalar member
        if ($type instanceof ReflectionUnionType) {
            foreach ($type->getTypes() as $innerType) {
                if ($innerType instanceof ReflectionNamedType && ! $innerType->allowsNull()) {
                    $innerName = $innerType->getName();
                    if ($innerName === 'string') {
                        return 'test-'.$name;
                    }
                    if ($innerName === 'int') {
                        return 42;
                    }
                    if ($innerName === 'float') {
                        return 99.99;
                    }
                    if ($innerName === 'bool') {
                        return true;
                    }
                }
            }
        }

        return null;
    }

    /**
     * Generate a fake value for a named type, handling complex types.
     *
     * For BackedEnums: returns the first case's backed value.
     * For ValueObjects: returns a scalar placeholder string.
     * For nested DTOs: returns null (requires nested structure).
     * For scalars: returns a type-appropriate default.
     *
     * @param  ReflectionNamedType  $type  The named type to generate a fake value for
     * @param  string  $name  The property name (used for generating contextual fake values)
     * @return mixed A type-appropriate fake value, or null if no value can be generated
     */
    private function fakeValueForComplexType(ReflectionNamedType $type, string $name): mixed
    {
        $typeName = $type->getName();

        // BackedEnum — use first case's backed value
        if (enum_exists($typeName) && is_a($typeName, \BackedEnum::class, true)) {
            $cases = $typeName::cases();
            if ($cases !== []) {
                return $cases[0]->value;
            }
        }

        // ValueObject — use a generic string placeholder
        if (class_exists($typeName) && is_a($typeName, ValueObjectContract::class, true)) {
            return 'test-'.$name;
        }

        // Nested DTO — return null (cannot generate nested structure without source data)
        if (class_exists($typeName) && is_subclass_of($typeName, DataTransferObject::class)) {
            return null;
        }

        // Carbon / DateTime — use ISO string
        if ($typeName === \Illuminate\Support\Carbon::class
            || $typeName === \Carbon\Carbon::class
            || is_a($typeName, \DateTimeInterface::class, true)
        ) {
            return '2024-01-15 10:30:00';
        }

        // DtoCollection — return empty array
        if ($typeName === \ZeroBoiler\DTO\DtoCollection::class) {
            return [];
        }

        // Scalar fallback
        return match ($typeName) {
            'string' => 'test-'.$name,
            'int' => 42,
            'float' => 99.99,
            'bool' => true,
            'array' => [],
            default => null,
        };
    }

    /**
     * Format a data array as a PHP literal string for the generated test.
     *
     * Handles scalars, arrays, null, and BackedEnum values (serialized
     * as their backed value).
     *
     * @param  array<string, mixed>  $data
     */
    private function formatDataAsPhp(array $data): string
    {
        if ($data === []) {
            return '[]';
        }

        $lines = ['['];
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $escaped = str_replace("'", "\'", $value);
                $lines[] = "    '{$key}' => '{$escaped}',";
            } elseif (is_int($value)) {
                $lines[] = "    '{$key}' => {$value},";
            } elseif (is_float($value)) {
                $lines[] = "    '{$key}' => {$value},";
            } elseif (is_bool($value)) {
                $lines[] = "    '{$key}' => ".($value ? 'true' : 'false').',';
            } elseif ($value instanceof \BackedEnum) {
                $backedValue = $value->value;
                if (is_int($backedValue)) {
                    $lines[] = "    '{$key}' => {$backedValue},";
                } else {
                    $escaped = str_replace("'", "\'", (string) $backedValue);
                    $lines[] = "    '{$key}' => '{$escaped}',";
                }
            } elseif (is_array($value)) {
                $lines[] = "    '{$key}' => [],";
            } elseif ($value === null) {
                $lines[] = "    '{$key}' => null,";
            }
        }
        $lines[] = ']';

        return implode("\n", $lines);
    }
}
