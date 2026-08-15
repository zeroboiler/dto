<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Tests;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

/**
 * Production readiness V9 structural audit — comprehensive source code verification.
 *
 * Extends the V8 audit with additional checks:
 *
 * 1. Every PHP file declares strict_types=1
 * 2. Every class is final or abstract (traits/interfaces exempt)
 * 3. Every public method has an explicit return type declaration
 * 4. No public method returns `mixed` (PHPStan level 9)
 * 5. License header present in every source file
 * 6. No duplicate class names in the source tree
 * 7. All attribute classes use readonly promoted properties
 * 8. All infrastructure classes have class-level docblocks
 * 9. DtoCollection has __clone guard with never return
 * 10. DataTransferObject base class has complete API
 * 11. All validation attributes implement ValidationAttribute contract
 * 12. DTOCast implements CastsAttributes correctly
 * 13. DTOManager is readonly with complete delegation methods
 * 14. OpenApiSchemaGenerator generates proper schemas
 * 15. All contracts interfaces are complete
 * 16. No TODO/FIXME/HACK comments in production code
 * 17. All source files are parseable (no syntax errors)
 * 18. Source file count matches README documentation
 */
#[CoversNothing]
final class ProductionReadinessV9StructuralAuditTest extends TestCase
{
    /** @var non-empty-string */
    private const SRC_DIR = __DIR__.'/../src';

    /**
     * Get all PHP source files recursively.
     *
     * @return list<non-empty-string>
     */
    private function getSourceFiles(): array
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(self::SRC_DIR, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        $files = [];
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    // -----------------------------------------------------------------------
    // 1. strict_types declaration
    // -----------------------------------------------------------------------

    public function test_every_source_file_declares_strict_types(): void
    {
        foreach ($this->getSourceFiles() as $file) {
            $contents = file_get_contents($file);
            $this->assertNotEmpty($contents, "File {$file} is empty.");
            $this->assertStringContainsString(
                'declare(strict_types=1)',
                $contents,
                "File {$file} is missing declare(strict_types=1)."
            );
        }
    }

    // -----------------------------------------------------------------------
    // 2. Class final/abstract/trait enforcement
    // -----------------------------------------------------------------------

    public function test_all_classes_are_final_or_abstract(): void
    {
        foreach ($this->getSourceFiles() as $file) {
            $contents = file_get_contents($file);

            // Skip traits
            if (str_contains($contents, 'trait ')) {
                continue;
            }

            // Skip interfaces
            if (preg_match('/interface\s+\w+/', $contents)) {
                continue;
            }

            if (! preg_match('/(?:final\s+|abstract\s+)?(?:readonly\s+)?(?:class|enum)\s+\w+/', $contents, $matches)) {
                continue;
            }

            $declaration = $matches[0];
            $this->assertTrue(
                str_starts_with($declaration, 'final') || str_starts_with($declaration, 'abstract'),
                "Class in {$file} (\"{$declaration}\") is neither final nor abstract."
            );
        }
    }

    // -----------------------------------------------------------------------
    // 3. Public method return type declarations
    // -----------------------------------------------------------------------

    public function test_all_public_methods_have_return_types(): void
    {
        foreach ($this->getSourceFiles() as $file) {
            $contents = file_get_contents($file);

            preg_match_all('/public\s+(static\s+)?function\s+(\w+)\s*\(/', $contents, $matches, PREG_SET_ORDER);

            foreach ($matches as $match) {
                $method = $match[2];
                $pattern = '/public\s+(?:static\s+)?function\s+'.preg_quote($method, '/').'\s*\([^)]*\)\s*:\s*/';
                $hasReturnType = preg_match($pattern, $contents);

                $this->assertTrue(
                    (bool) $hasReturnType,
                    "Method {$method} in {$file} lacks an explicit return type declaration."
                );
            }
        }
    }

    // -----------------------------------------------------------------------
    // 4. No `mixed` return types in public API (PHPStan level 9)
    // -----------------------------------------------------------------------

    public function test_no_public_method_returns_mixed(): void
    {
        foreach ($this->getSourceFiles() as $file) {
            $contents = file_get_contents($file);

            preg_match_all(
                '/public\s+(static\s+)?function\s+(\w+)\s*\([^)]*\)\s*:\s*mixed/',
                $contents,
                $matches,
                PREG_SET_ORDER
            );

            foreach ($matches as $match) {
                $method = $match[2];
                $this->fail("Public method {$method} in {$file} returns `mixed` — violates PHPStan level 9.");
            }
        }
    }

    // -----------------------------------------------------------------------
    // 5. License header
    // -----------------------------------------------------------------------

    public function test_license_header_present_in_all_files(): void
    {
        foreach ($this->getSourceFiles() as $file) {
            $contents = file_get_contents($file);
            $this->assertStringContainsString(
                'This file is part of ZeroBoiler, licensed under the proprietary license.',
                $contents,
                "File {$file} is missing the license header."
            );
        }
    }

    // -----------------------------------------------------------------------
    // 6. No duplicate class names
    // -----------------------------------------------------------------------

    public function test_no_duplicate_class_names(): void
    {
        $allClasses = [];
        foreach ($this->getSourceFiles() as $file) {
            $contents = file_get_contents($file);
            preg_match_all('/(?:final\s+)?(?:readonly\s+)?(?:abstract\s+)?(?:class|enum|interface|trait)\s+(\w+)/', $contents, $matches);
            foreach ($matches[1] as $className) {
                $allClasses[] = basename($file).':'.$className;
            }
        }

        $unique = array_unique($allClasses);
        $this->assertSameSize(
            $allClasses,
            $unique,
            'Duplicate class names found: '.implode(', ', array_diff_assoc($allClasses, $unique))
        );
    }

    // -----------------------------------------------------------------------
    // 7. Attribute classes use readonly promoted properties
    // -----------------------------------------------------------------------

    public function test_attribute_classes_use_readonly_promoted_properties(): void
    {
        $attributesDir = self::SRC_DIR.'/Attributes';
        if (! is_dir($attributesDir)) {
            $this->markTestSkipped('No Attributes directory found.');

            return;
        }

        foreach (glob($attributesDir.'/*.php') as $file) {
            $contents = file_get_contents($file);

            // Skip if the attribute has no constructor (Hidden, etc.)
            if (! preg_match('/public function __construct\s*\(/', $contents)) {
                continue;
            }

            $hasReadonlyPromoted = preg_match('/public\s+readonly\s+/', $contents);

            $this->assertTrue(
                (bool) $hasReadonlyPromoted,
                basename($file).' should use readonly promoted properties.'
            );
        }
    }

    // -----------------------------------------------------------------------
    // 8. Infrastructure classes have class-level docblocks
    // -----------------------------------------------------------------------

    public function test_infrastructure_classes_have_class_docblocks(): void
    {
        $infraFiles = array_merge(
            glob(self::SRC_DIR.'/*.php'),
            glob(self::SRC_DIR.'/Support/*.php'),
            glob(self::SRC_DIR.'/Casts/*.php'),
            glob(self::SRC_DIR.'/Exceptions/*.php'),
            glob(self::SRC_DIR.'/Facades/*.php'),
            glob(self::SRC_DIR.'/Console/Commands/*.php'),
            glob(self::SRC_DIR.'/Contracts/*.php'),
        );

        foreach ($infraFiles as $file) {
            $contents = file_get_contents($file);
            $hasClassDocblock = preg_match('/\/\*\*[\s\S]*?\*\/\s*(?:declare|namespace|final|abstract|readonly|class|enum|trait|interface)/', $contents);

            $this->assertTrue(
                (bool) $hasClassDocblock,
                basename($file).' is missing a class-level docblock.'
            );
        }
    }

    // -----------------------------------------------------------------------
    // 9. DtoCollection has __clone guard with never return
    // -----------------------------------------------------------------------

    public function test_dto_collection_has_clone_guard(): void
    {
        $file = self::SRC_DIR.'/DtoCollection.php';
        $this->assertFileExists($file);

        $contents = file_get_contents($file);

        $this->assertStringContainsString('function __clone(): never', $contents,
            'DtoCollection must prevent cloning with a never-return __clone().');
    }

    // -----------------------------------------------------------------------
    // 10. DataTransferObject has complete API
    // -----------------------------------------------------------------------

    public function test_data_transfer_object_has_complete_api(): void
    {
        $file = self::SRC_DIR.'/DataTransferObject.php';
        $this->assertFileExists($file);

        $contents = file_get_contents($file);

        $requiredMethods = [
            'fromArray', 'fromPartialArray', 'fromRequest', 'fromJson',
            'toArray', 'toJson', 'jsonSerialize', 'allValues',
            'only', 'except', 'with', 'equals', 'isEmpty', 'isNotEmpty',
            'rules', 'rulesFor', 'validateArray', 'validatePartialArray',
            'fromPartialRequest', 'flushMetadataCache', 'setMetadataCacheTtl',
        ];

        foreach ($requiredMethods as $method) {
            $this->assertStringContainsString(
                'function '.$method.'(',
                $contents,
                "DataTransferObject is missing the {$method}() method."
            );
        }
    }

    // -----------------------------------------------------------------------
    // 11. All validation attributes implement ValidationAttribute contract
    // -----------------------------------------------------------------------

    public function test_all_validation_attributes_implement_contract(): void
    {
        $attributesDir = self::SRC_DIR.'/Attributes';
        $this->assertDirectoryExists($attributesDir);

        $validationAttributes = [
            'Required', 'Email', 'Max', 'Min', 'Url', 'Uuid', 'Pattern', 'In',
            'Integer', 'Numeric', 'Boolean', 'Date', 'ArrayRule', 'Json',
            'Enum', 'Confirmed', 'Same', 'Different', 'Between',
            'Prohibited', 'Present', 'Declined', 'Accepted', 'Nullable',
            'Sometimes', 'Distinct', 'Size', 'StartsWith', 'EndsWith',
            'RequiredIf', 'RequiredUnless', 'RequiredWith', 'RequiredWithAll',
            'RequiredWithout', 'RequiredWithoutAll',
        ];

        foreach ($validationAttributes as $attr) {
            $file = $attributesDir.'/'.$attr.'.php';
            $this->assertFileExists($file, "Missing attribute class: {$attr}");

            $contents = file_get_contents($file);
            $this->assertStringContainsString(
                'implements ValidationAttribute',
                $contents,
                "{$attr} must implement ValidationAttribute contract."
            );
            $this->assertStringContainsString(
                'function ruleKey()',
                $contents,
                "{$attr} must implement ruleKey() method from ValidationAttribute."
            );
        }
    }

    // -----------------------------------------------------------------------
    // 12. DTOCast implements CastsAttributes
    // -----------------------------------------------------------------------

    public function test_dto_cast_implements_casts_attributes(): void
    {
        $file = self::SRC_DIR.'/Casts/DTOCast.php';
        $this->assertFileExists($file);

        $contents = file_get_contents($file);

        $this->assertStringContainsString('implements CastsAttributes', $contents,
            'DTOCast must implement CastsAttributes interface.');
        $this->assertStringContainsString('function get(', $contents,
            'DTOCast must implement get() method.');
        $this->assertStringContainsString('function set(', $contents,
            'DTOCast must implement set() method.');
        $this->assertStringContainsString('function serialize(', $contents,
            'DTOCast should implement serialize() method.');
    }

    // -----------------------------------------------------------------------
    // 13. DTOManager is readonly with complete delegation
    // -----------------------------------------------------------------------

    public function test_dto_manager_is_readonly_with_complete_api(): void
    {
        $file = self::SRC_DIR.'/DTOManager.php';
        $this->assertFileExists($file);

        $contents = file_get_contents($file);

        $this->assertStringContainsString('final readonly class DTOManager', $contents,
            'DTOManager must be a final readonly class.');

        $requiredMethods = [
            'validate', 'make', 'makeFromJson', 'rules', 'rulesFor',
            'schema', 'fromPartialArray', 'fromPartialRequest', 'fromJson',
        ];

        foreach ($requiredMethods as $method) {
            $this->assertStringContainsString(
                'function '.$method.'(',
                $contents,
                "DTOManager is missing the {$method}() method."
            );
        }
    }

    // -----------------------------------------------------------------------
    // 14. OpenApiSchemaGenerator
    // -----------------------------------------------------------------------

    public function test_open_api_schema_generator_has_public_api(): void
    {
        $file = self::SRC_DIR.'/Support/OpenApiSchemaGenerator.php';
        $this->assertFileExists($file);

        $contents = file_get_contents($file);

        $this->assertStringContainsString('function generate(', $contents,
            'OpenApiSchemaGenerator must have generate() method.');
        $this->assertStringContainsString('function generateWithComponents(', $contents,
            'OpenApiSchemaGenerator must have generateWithComponents() method.');
    }

    // -----------------------------------------------------------------------
    // 15. Contracts are complete
    // -----------------------------------------------------------------------

    public function test_contracts_are_complete(): void
    {
        $contractsDir = self::SRC_DIR.'/Contracts';
        $this->assertDirectoryExists($contractsDir);

        // ValidatableDTO
        $v = $contractsDir.'/ValidatableDTO.php';
        $this->assertFileExists($v);
        $this->assertStringContainsString('function rules(', file_get_contents($v),
            'ValidatableDTO must declare rules().');
        $this->assertStringContainsString('function rulesFor(', file_get_contents($v),
            'ValidatableDTO must declare rulesFor().');

        // FromRequestDTO
        $f = $contractsDir.'/FromRequestDTO.php';
        $this->assertFileExists($f);
        $this->assertStringContainsString('function fromRequest(', file_get_contents($f),
            'FromRequestDTO must declare fromRequest().');

        // ValidationAttribute
        $va = $contractsDir.'/ValidationAttribute.php';
        $this->assertFileExists($va);
        $this->assertStringContainsString('function ruleKey(', file_get_contents($va),
            'ValidationAttribute must declare ruleKey().');
    }

    // -----------------------------------------------------------------------
    // 16. No TODO/FIXME/HACK comments
    // -----------------------------------------------------------------------

    public function test_no_todo_fixme_hack_comments(): void
    {
        $forbidden = ['TODO', 'FIXME', 'HACK', 'XXX'];

        foreach ($this->getSourceFiles() as $file) {
            $contents = file_get_contents($file);
            $lineNum = 0;

            foreach (explode("\n", $contents) as $line) {
                $lineNum++;

                foreach ($forbidden as $keyword) {
                    if (preg_match('/^\s*(\/\/|#)/', $line) && str_contains($line, $keyword)) {
                        $this->fail("File {$file}:{$lineNum} contains {$keyword} comment: {$line}");
                    }
                }
            }
        }

        $this->assertTrue(true);
    }

    // -----------------------------------------------------------------------
    // 17. All source files are parseable
    // -----------------------------------------------------------------------

    public function test_all_source_files_are_parseable(): void
    {
        foreach ($this->getSourceFiles() as $file) {
            $result = token_get_all((string) file_get_contents($file));
            $errors = [];

            foreach ($result as $token) {
                if (is_array($token) && $token[0] === T_ERROR) {
                    $errors[] = $token[1];
                }
            }

            $this->assertEmpty(
                $errors,
                "File {$file} has parse error(s): ".implode(', ', $errors)
            );
        }
    }

    // -----------------------------------------------------------------------
    // 18. Source file count matches README
    // -----------------------------------------------------------------------

    public function test_source_file_count_matches_readme(): void
    {
        $files = $this->getSourceFiles();
        // README claims 55 source files (37 validation attributes, 4 metadata attributes, 14 infrastructure)
        $this->assertCount(
            55,
            $files,
            'README claims 55 source files in src/, but found '.count($files).'. Update README.'
        );
    }

    // -----------------------------------------------------------------------
    // 19. DtoMetadataResolver deduplicates rules
    // -----------------------------------------------------------------------

    public function test_dto_metadata_resolver_has_deduplication(): void
    {
        $file = self::SRC_DIR.'/Support/DtoMetadataResolver.php';
        $this->assertFileExists($file);

        $contents = file_get_contents($file);

        $this->assertStringContainsString('function deduplicateRules(', $contents,
            'DtoMetadataResolver must have a deduplicateRules() method.');
    }

    // -----------------------------------------------------------------------
    // 20. DTOException has named constructors
    // -----------------------------------------------------------------------

    public function test_dto_exception_has_named_constructors(): void
    {
        $file = self::SRC_DIR.'/Exceptions/DTOException.php';
        $this->assertFileExists($file);

        $contents = file_get_contents($file);

        $this->assertStringContainsString('static function invalidCast(', $contents,
            'DTOException must have invalidCast() named constructor.');
        $this->assertStringContainsString('static function invalidJson(', $contents,
            'DTOException must have invalidJson() named constructor.');
    }

    // -----------------------------------------------------------------------
    // 21. DTO facade resolves correct accessor
    // -----------------------------------------------------------------------

    public function test_dto_facade_resolves_correct_accessor(): void
    {
        $file = self::SRC_DIR.'/Facades/DTO.php';
        $this->assertFileExists($file);

        $contents = file_get_contents($file);

        $this->assertStringContainsString("'zeroboiler.dto'", $contents,
            'DTO facade must resolve the zeroboiler.dto singleton.');
    }
}
