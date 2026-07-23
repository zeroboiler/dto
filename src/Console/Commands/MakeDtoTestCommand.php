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
 *   php artisan zeroboiler:dto-test "App\DTO\CreateUserDTO"
 */
final class MakeDtoTestCommand extends Command
{
    protected $signature = 'zeroboiler:dto-test {class : The DTO class FQN} {--dir= : Output directory}';

    protected $description = 'Generate Pest tests for a ZeroBoiler DTO class';

    public function handle(): int
    {
        $dtoClass = (string) $this->argument('class');

        if (! class_exists($dtoClass)) {
            $this->error("DTO class '{$dtoClass}' not found.");

            return self::FAILURE;
        }

        $shortName = class_basename($dtoClass);
        $defaultDir = base_path('tests/Unit/DTO');
        $dir = (string) ($this->option('dir') ?? $defaultDir);
        $path = rtrim($dir, '/')."/{$shortName}Test.php";

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        if (file_exists($path) && ! $this->confirm("File {$path} already exists. Overwrite?")) {
            $this->info('Cancelled.');

            return self::SUCCESS;
        }

        $fakeData = self::generateFakeData($dtoClass);
        $fakeDataStr = self::formatDataAsPhp($fakeData);

        $rules = $dtoClass::rules();
        $ruleStrings = [];
        foreach ($rules as $field => $fieldRules) {
            $ruleStrings[] = "    '{$field}' => [".implode(', ', array_map(fn ($r): string => "'{$r}'", $fieldRules)).'],';
        }
        $rulesStr = implode("\n", $ruleStrings);

        $content = <<<PHP
<?php

declare(strict_types=1);

use {$dtoClass};

describe('{$shortName}', function () {
    it('has validation rules', function () {
        \$rules = {$shortName}::rules();

        expect(\$rules)->toBeArray();
        expect(\$rules)->not->toBeEmpty();
    });

    it('can be created from array', function () {
        \$data = {$fakeDataStr};
        \$dto = {$shortName}::fromArray(\$data, validate: false);

        expect(\$dto)->toBeInstanceOf({$shortName}::class);
    });

    it('can be converted to array', function () {
        \$data = {$fakeDataStr};
        \$dto = {$shortName}::fromArray(\$data, validate: false);

        expect(\$dto->toArray())->toBeArray();
    });

    it('can be serialized to JSON', function () {
        \$data = {$fakeDataStr};
        \$dto = {$shortName}::fromArray(\$data, validate: false);

        expect(\$dto->toJson())->toBeJson();
    });

    it('rules match expected', function () {
        \$rules = {$shortName}::rules();

        \$expected = [
{$rulesStr}
        ];

        foreach (\$expected as \$field => \$fieldRules) {
            expect(\$rules)->toHaveKey(\$field);
        }
    });
});
PHP;

        file_put_contents($path, $content);
        $relative = str_replace(base_path().'/', '', $path);
        $this->info("Generated: {$relative}");

        return self::SUCCESS;
    }

    /**
     * Generate fake data for a DTO class by reading its properties via reflection.
     *
     * @param  class-string  $dtoClass
     * @return array<string, mixed>
     */
    private static function generateFakeData(string $dtoClass): array
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

            $data[$name] = self::fakeValueForType($type, $name);
        }

        return $data;
    }

    /**
     * Generate a type-appropriate fake value for a property.
     *
     * Uses the property name to generate more realistic values
     * (e.g. "email" fields get fake email addresses).
     */
    private static function fakeValueForType(\ReflectionType $type, string $name): mixed
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
                'int' => random_int(1, 100),
                'float' => round(mt_rand(1, 10000) / 100, 2),
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
    private static function formatDataAsPhp(array $data): string
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
