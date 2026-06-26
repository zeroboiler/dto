<?php

declare(strict_types=1);

namespace ZeroBoiler\DTO\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

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

        if (!class_exists($dtoClass)) {
            $this->error("DTO class '{$dtoClass}' not found.");

            return self::FAILURE;
        }

        $shortName = class_basename($dtoClass);
        $defaultDir = base_path('tests/Unit/DTO');
        $dir = (string) ($this->option('dir') ?? $defaultDir);
        $path = rtrim($dir, '/') . "/{$shortName}Test.php";

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        if (file_exists($path) && !$this->confirm("File {$path} already exists. Overwrite?")) {
            $this->info('Cancelled.');

            return self::SUCCESS;
        }

        $rules = $dtoClass::rules();
        $ruleStrings = [];
        foreach ($rules as $field => $fieldRules) {
            $ruleStrings[] = "    '{$field}' => [" . implode(', ', array_map(fn($r) => "'{$r}'", $fieldRules)) . "],";
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
        \$data = []; // TODO: add valid data
        \$dto = {$shortName}::fromArray(\$data, validate: false);

        expect(\$dto)->toBeInstanceOf({$shortName}::class);
    });

    it('can be converted to array', function () {
        \$data = []; // TODO: add valid data
        \$dto = {$shortName}::fromArray(\$data, validate: false);

        expect(\$dto->toArray())->toBeArray();
    });

    it('can be serialized to JSON', function () {
        \$data = []; // TODO: add valid data
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
        $relative = str_replace(base_path() . '/', '', $path);
        $this->info("Generated: {$relative}");

        return self::SUCCESS;
    }
}
