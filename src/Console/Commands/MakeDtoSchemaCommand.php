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
 */
final class MakeDtoSchemaCommand extends Command
{
    #[\Override]
    protected $signature = 'zeroboiler:dto-schema {class : The DTO class FQN} {--json : Output as raw JSON}';

    #[\Override]
    protected $description = 'Generate OpenAPI schema from a ZeroBoiler DTO class';

    public function handle(): int
    {
        $dtoClass = (string) $this->argument('class');

        if (! class_exists($dtoClass)) {
            $this->error("DTO class '{$dtoClass}' not found.");

            return self::FAILURE;
        }

        $schema = OpenApiSchemaGenerator::generate($dtoClass);

        if ($this->option('json')) {
            $this->line(json_encode($schema, JSON_PRETTY_PRINT));
        } else {
            $this->info('Schema for: '.class_basename($dtoClass));
            $this->newLine();
            $this->line(json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }

        return self::SUCCESS;
    }
}
