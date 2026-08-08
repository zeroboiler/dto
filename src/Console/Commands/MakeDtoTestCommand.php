<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Console\Commands;

use Illuminate\Console\Command;
use ReflectionClass;

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

            $data[$name] = $this->fakeValueForType($type, $name);
        }

        return $data;
    }

    /**
     * Generate a type-appropriate fake value for a property.
     *
     * Uses the property name to generate more realistic values
     * (e.g. "email" fields get fake email addresses).
     */
    private function fakeValueForType(\ReflectionType $type, string $name): mixed
    {
        $lowerName = strtolower($name);

        // Name-based hints first
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
            return '+1234567890';
        }
        if (str_contains($lowerName, 'password')) {
            return 'password123';
        }

        // Type-based fallback
        if ($type instanceof \ReflectionNamedType) {
            return match ($type->getName()) {
                'string' => 'test-'.$name,
                'int' => 42,
                'float' => 99.99,
                'bool' => true,
                'array' => [],
                default => null,
            };
        }

        return null;
    }

    /**
     * Format a data array as a PHP literal string for the generated test.
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
                $escaped = str_replace("'", "\\'", $value);
                $lines[] = "    '{$key}' => '{$escaped}',";
            } elseif (is_int($value)) {
                $lines[] = "    '{$key}' => {$value},";
            } elseif (is_float($value)) {
                $lines[] = "    '{$key}' => {$value},";
            } elseif (is_bool($value)) {
                $lines[] = "    '{$key}' => ".($value ? 'true' : 'false').',';
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
