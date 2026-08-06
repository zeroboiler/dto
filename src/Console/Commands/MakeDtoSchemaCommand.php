<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Console\Commands;

use Illuminate\Console\Command;
use ZeroBoiler\DTO\Support\OpenApiSchemaGenerator;

/**
 * Generate OpenAPI schema for a DTO class.
 *
 *   php artisan zeroboiler:dto-schema "App\DTO\CreateUserDTO"
 *   php artisan zeroboiler:dto-schema "App\DTO\CreateUserDTO" --json
 *
 * Outputs the generated OpenAPI 3.0 schema to the console.
 * For DTOs with nested DTO references, use {@see OpenApiSchemaGenerator::generateWithComponents()}
 * to get component schema definitions alongside the main schema.
 */
final class MakeDtoSchemaCommand extends Command
{
    protected string $signature = 'zeroboiler:dto-schema {class : The DTO class FQN} {--json : Output as raw JSON}';

    protected string $description = 'Generate OpenAPI schema from a ZeroBoiler DTO class';

    #[\Override]
    public function handle(): int
    {
        /** @var string $dtoClass */
        $dtoClass = $this->argument('class');

        if (! class_exists($dtoClass)) {
            $this->error("DTO class '{$dtoClass}' not found.");

            return self::FAILURE;
        }

        try {
            $schema = OpenApiSchemaGenerator::generate($dtoClass);
        } catch (\LogicException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        if ($this->option('json')) {
            $this->line(json_encode($schema, JSON_PRETTY_PRINT) ?: '');
        } else {
            $this->info('Schema for: '.class_basename($dtoClass));
            $this->newLine();
            $this->line(json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '');
        }

        return self::SUCCESS;
    }
}
