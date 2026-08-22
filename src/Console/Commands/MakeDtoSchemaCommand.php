<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Console\Commands;

use Illuminate\Console\Command;
use ZeroBoiler\DTO\Support\OpenApiSchemaGenerator;

/**
 * Generate OpenAPI 3.0 schema for a DTO class.
 *
 * Reads the DTO's constructor parameters and validation attributes via
 * reflection to produce an accurate OpenAPI schema with types, constraints,
 * required-field lists, and nested DTO component references.
 *
 *   php artisan zeroboiler:dto-schema "App\DTO\CreateUserDTO"
 *   php artisan zeroboiler:dto-schema "App\DTO\CreateUserDTO" --output=schemas/
 *   php artisan zeroboiler:dto-schema "App\DTO\CreateUserDTO" --with-components
 *
 * @see \ZeroBoiler\DTO\Support\OpenApiSchemaGenerator For the schema generation logic
 */
final class MakeDtoSchemaCommand extends Command
{
    protected $signature = 'zeroboiler:dto-schema {class : The DTO class FQN}
        {--output= : Output directory for the generated JSON schema file}
        {--with-components : Include component schemas for nested DTOs}
        {--json : Output as JSON string to stdout (alias for --print)}
        {--print : Print the schema to stdout instead of writing to a file}';

    protected $description = 'Generate OpenAPI 3.0 schema for a ZeroBoiler DTO class';

    public function handle(): int
    {
        /** @var string $dtoClass */
        $dtoClass = $this->argument('class');

        if (! class_exists($dtoClass)) {
            $this->error("DTO class '{$dtoClass}' not found.");

            return self::FAILURE;
        }

        $shortName = class_basename($dtoClass);

        try {
            if ($this->option('with-components')) {
                /** @var array{schema: array<string, mixed>, components: array{schemas: array<string, array<string, mixed>>}} $schema */
                $schema = OpenApiSchemaGenerator::generateWithComponents($dtoClass);
            } else {
                /** @var array<string, mixed> $schema */
                $schema = OpenApiSchemaGenerator::generate($dtoClass);
            }
        } catch (\LogicException $e) {
            $this->error($e->getMessage());
            $this->newLine();
            $this->info('Tip: Use --with-components to generate schemas with nested DTO references.');

            return self::FAILURE;
        } catch (\ReflectionException $e) {
            $this->error("Failed to reflect DTO class '{$dtoClass}': {$e->getMessage()}");

            return self::FAILURE;
        }

        $json = json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if ($json === false) {
            $this->error('Failed to encode schema as JSON.');

            return self::FAILURE;
        }

        // --print or --json: output to stdout
        if ($this->option('print') || $this->option('json')) {
            $this->line($json);

            return self::SUCCESS;
        }

        // Write to file
        $defaultDir = \function_exists('base_path') ? base_path('schemas') : getcwd().'/schemas';
        /** @var string|null $optDir */
        $optDir = $this->option('output');
        $dir = $optDir ?? $defaultDir;
        $path = rtrim($dir, '/\\')."/{$shortName}Schema.json";

        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        if (file_exists($path) && ! $this->confirm("File {$path} already exists. Overwrite?")) {
            $this->info('Cancelled.');

            return self::SUCCESS;
        }

        file_put_contents($path, $json.PHP_EOL);

        $basePath = \function_exists('base_path') ? base_path().'/' : getcwd().'/';
        $relative = str_replace($basePath, '', $path);
        $this->info("Generated: {$relative}");

        return self::SUCCESS;
    }
}
