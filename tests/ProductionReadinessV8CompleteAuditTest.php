<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Tests;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

/**
 * Production readiness audit — verifies all source files meet strict quality criteria.
 *
 * This test is a meta-audit that inspects the source code structure
 * without executing runtime behavior. It ensures:
 *
 * 1. Every PHP file declares strict_types=1
 * 2. Every class/interface/trait is either final or abstract or a trait
 * 3. Every public method has an explicit return type declaration
 * 4. No public method returns `mixed` (PHPStan level 9 requirement)
 * 5. Every attribute class uses readonly promoted properties
 * 6. License header is present in every file
 * 7. No duplicate class names exist in the source tree
 * 8. All validation attributes implement ValidationAttribute contract
 * 9. DataTransferObject base class has complete API
 */
#[CoversNothing]
final class ProductionReadinessV8CompleteAuditTest extends TestCase
{
    /** @var non-empty-string */
    private const SRC_DIR = __DIR__.'/../src';

    /**
     * Get all PHP files in the src directory recursively.
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

    /**
     * Every PHP source file must declare strict_types=1.
     */
    public function test_every_source_file_declares_strict_types(): void
    {
        $files = $this->getSourceFiles();

        foreach ($files as $file) {
            $contents = file_get_contents($file);
            $this->assertNotEmpty(
                $contents,
                "File {$file} is empty."
            );

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

    /**
     * All classes must be either final, abstract, or traits (no open inheritance).
     */
    public function test_all_classes_are_final_or_abstract_or_trait(): void
    {
        $files = $this->getSourceFiles();

        foreach ($files as $file) {
            $tokens = token_get_all((string) file_get_contents($file));
            $className = $this->extractClassName($file, $tokens);
            $type = $this->getClassType($tokens);

            // Skip interfaces — they can't be final
            if ($type === 'interface') {
                continue;
            }

            // Skip traits — they can't be final
            if ($type === 'trait') {
                continue;
            }

            $this->assertNotEmpty(
                $className,
                "File {$file} contains a class without a detectable name."
            );

            $this->assertContains(
                $type,
                ['final', 'abstract'],
                "Class {$className} in {$file} is neither final nor abstract."
            );
        }
    }

    // -----------------------------------------------------------------------
    // 3. Public method return type declarations
    // -----------------------------------------------------------------------

    /**
     * Every public method must have an explicit return type declaration.
     */
    public function test_all_public_methods_have_return_types(): void
    {
        $files = $this->getSourceFiles();

        foreach ($files as $file) {
            $contents = file_get_contents($file);

            if (! preg_match_all(
                '/public\s+static\s+function\s+(\w+)\s*\(/',
                $contents,
                $staticMethods
            )) {
                $staticMethods[1] = [];
            }

            if (! preg_match_all(
                '/public\s+function\s+(\w+)\s*\(/',
                $contents,
                $instanceMethods
            )) {
                $instanceMethods[1] = [];
            }

            $allMethods = array_merge($staticMethods[1], $instanceMethods[1]);

            foreach ($allMethods as $method) {
                $hasReturnType = preg_match(
                    '/public\s+(static\s+)?function\s+'
                    . preg_quote($method, '/')
                    . '\s*\([^)]*\)\s*:\s*/',
                    $contents
                );

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

    /**
     * No public method should use `mixed` as its return type.
     *
     * Internal/private methods may use mixed for generic casting.
     */
    public function test_no_public_method_returns_mixed(): void
    {
        $files = $this->getSourceFiles();

        foreach ($files as $file) {
            $contents = file_get_contents($file);

            preg_match_all(
                '/public\s+(static\s+)?function\s+(\w+)\s*\([^)]*\)\s*:\s*mixed\s*({|;)/',
                $contents,
                $matches,
                PREG_SET_ORDER
            );

            foreach ($matches as $match) {
                $method = $match[2];
                $this->fail(
                    "Public method {$method} in {$file} returns `mixed`. "
                    . 'Use a specific type for PHPStan level 9 compliance.'
                );
            }

            $this->assertTrue(true);
        }
    }

    // -----------------------------------------------------------------------
    // 5. Attribute classes use readonly promoted properties
    // -----------------------------------------------------------------------

    /**
     * All attribute classes (in the Attributes namespace) must use
     * readonly promoted constructor properties.
     */
    public function test_attribute_classes_use_readonly_properties(): void
    {
        $files = $this->getSourceFiles();

        foreach ($files as $file) {
            if (! str_contains($file, '/Attributes/')) {
                continue;
            }

            $contents = file_get_contents($file);

            if (! str_contains($contents, 'class ')) {
                continue;
            }

            $this->assertMatchesRegularExpression(
                '/public\s+readonly/',
                $contents,
                "Attribute class in {$file} must use public readonly promoted properties."
            );
        }
    }

    // -----------------------------------------------------------------------
    // 6. License header present
    // -----------------------------------------------------------------------

    /**
     * Every source file must contain the ZeroBoiler license header.
     */
    public function test_every_source_file_has_license_header(): void
    {
        $files = $this->getSourceFiles();

        foreach ($files as $file) {
            $contents = file_get_contents($file);

            $this->assertStringContainsString(
                'This file is part of ZeroBoiler',
                $contents,
                "File {$file} is missing the ZeroBoiler license header."
            );
        }
    }

    // -----------------------------------------------------------------------
    // 7. No duplicate class names
    // -----------------------------------------------------------------------

    /**
     * No two source files should define the same class name.
     */
    public function test_no_duplicate_class_names(): void
    {
        $files = $this->getSourceFiles();
        $classNames = [];

        foreach ($files as $file) {
            $contents = file_get_contents($file);

            if (! preg_match('/\b(?:final|abstract)?\s+(?:class|interface|trait)\s+(\w+)/', $contents, $match)) {
                continue;
            }

            $className = $match[1];

            if (isset($classNames[$className])) {
                $this->fail(
                    "Duplicate class name '{$className}' found in both "
                    . $classNames[$className] . ' and ' . $file
                );
            }

            $classNames[$className] = $file;
        }

        $this->assertTrue(true);
    }

    // -----------------------------------------------------------------------
    // 8. All validation attributes implement ValidationAttribute contract
    // -----------------------------------------------------------------------

    /**
     * Attributes in the Attributes namespace that generate Laravel validation
     * rules must implement the ValidationAttribute interface (which requires
     * a ruleKey() method).
     */
    public function test_validation_attributes_implement_contract(): void
    {
        $files = $this->getSourceFiles();
        $contractInterface = 'ValidationAttribute';

        foreach ($files as $file) {
            if (! str_contains($file, '/Attributes/')) {
                continue;
            }

            $contents = file_get_contents($file);

            if (! str_contains($contents, 'class ')) {
                continue;
            }

            // Metadata-only attributes (not implementing ValidationAttribute) are valid
            if (str_contains($contents, 'MapFrom')
                || str_contains($contents, 'DefaultValue')
                || str_contains($contents, 'Hidden')
                || str_contains($contents, 'Cast ')
            ) {
                // These are metadata-only — they should NOT implement ValidationAttribute
                continue;
            }

            // Check that it implements ValidationAttribute
            if (str_contains($contents, 'implements ')) {
                $this->assertStringContainsString(
                    $contractInterface,
                    $contents,
                    "Validation attribute in {$file} must implement {$contractInterface} interface."
                );
            }
        }

        $this->assertTrue(true);
    }

    // -----------------------------------------------------------------------
    // 9. DataTransferObject base class has complete API
    // -----------------------------------------------------------------------

    /**
     * Verify the abstract DataTransferObject base class provides
     * all expected public and static methods.
     */
    public function test_data_transfer_object_has_complete_api(): void
    {
        $file = self::SRC_DIR . '/DataTransferObject.php';
        $this->assertFileExists($file);

        $contents = file_get_contents($file);

        $expectedMethods = [
            'public static function fromArray(array $data, bool $validate = true): static',
            'public static function fromPartialArray(array $data, bool $validatePresent = true): static',
            'public static function fromRequest(Request $request, bool $validate = true): static',
            'public static function fromPartialRequest(Request $request, bool $validate = true): static',
            'public static function fromJson(string $json, bool $validate = true): static',
            'public static function validateArray(array $data): array',
            'public static function rules(): array',
            'public static function rulesFor(string $action): array',
            'public function toArray(): array',
            'public function allValues(): array',
            'public function toJson(int $options = 0): string',
            'public function jsonSerialize(): array',
            'public function equals(self $other): bool',
            'public function isEmpty(): bool',
            'public function isNotEmpty(): bool',
            'public function only(array|string $keys): array',
            'public function except(array|string $keys): array',
            'public function with(array $overrides, bool $validate = true): static',
        ];

        foreach ($expectedMethods as $method) {
            $this->assertStringContainsString(
                $method,
                $contents,
                "DataTransferObject is missing method signature: {$method}"
            );
        }
    }

    // -----------------------------------------------------------------------
    // 10. DtoCollection has complete API
    // -----------------------------------------------------------------------

    /**
     * Verify DtoCollection provides all expected methods.
     */
    public function test_dto_collection_has_complete_api(): void
    {
        $file = self::SRC_DIR . '/DtoCollection.php';
        $this->assertFileExists($file);

        $contents = file_get_contents($file);

        $expectedMethods = [
            'public function toArray(): array',
            'public function allValues(): array',
            'public function items(): array',
            'public function count(): int',
            'public function getIterator(): Traversable',
            'public function offsetExists(mixed $offset): bool',
            'public function offsetGet(mixed $offset): mixed',
            'public function offsetSet(mixed $offset, mixed $value): void',
            'public function offsetUnset(mixed $offset): void',
            'public function jsonSerialize(): array',
            'public static function make(array $items = []): self',
            'public function push(DataTransferObject $dto): self',
            'public function first(): ?DataTransferObject',
            'public function last(): ?DataTransferObject',
            'public function map(callable $callback): array',
            'public function filter(callable $callback): self',
            'public function pluck(string $key): array',
            'public function pluckKey(string $keyField, ?string $valueField = null): array',
            'public function append(DataTransferObject $dto): self',
            'public function merge(self $other): self',
            'public function isEmpty(): bool',
            'public function isNotEmpty(): bool',
            'public function toArrayBy(string $keyField): array',
            'public function toDictionary(string $keyField, string $valueField): array',
        ];

        foreach ($expectedMethods as $method) {
            $this->assertStringContainsString(
                $method,
                $contents,
                "DtoCollection is missing method signature: {$method}"
            );
        }
    }

    // -----------------------------------------------------------------------
    // 11. DTOManager is readonly final class
    // -----------------------------------------------------------------------

    /**
     * Verify DTOManager uses readonly class modifier (PHP 8.5 feature).
     */
    public function test_dto_manager_is_readonly_final_class(): void
    {
        $file = self::SRC_DIR . '/DTOManager.php';
        $this->assertFileExists($file);

        $contents = file_get_contents($file);

        $this->assertStringContainsString('final readonly class DTOManager', $contents);
    }

    // -----------------------------------------------------------------------
    // 12. Facade has correct accessor
    // -----------------------------------------------------------------------

    /**
     * Verify the DTO facade is bound to the correct accessor key.
     */
    public function test_facade_accessor_key(): void
    {
        $file = self::SRC_DIR . '/Facades/DTO.php';
        $this->assertFileExists($file);

        $contents = file_get_contents($file);

        $this->assertStringContainsString("'zeroboiler.dto'", $contents);
        $this->assertStringContainsString('final class DTO extends Facade', $contents);
    }

    // -----------------------------------------------------------------------
    // 13. ServiceProvider registers singleton
    // -----------------------------------------------------------------------

    /**
     * Verify the service provider registers DTOManager as a singleton.
     */
    public function test_service_provider_registers_singleton(): void
    {
        $file = self::SRC_DIR . '/DTOSServiceProvider.php';
        $this->assertFileExists($file);

        $contents = file_get_contents($file);

        $this->assertStringContainsString("'zeroboiler.dto'", $contents);
        $this->assertStringContainsString('singleton', $contents);
        $this->assertStringContainsString('DTOManager', $contents);
        $this->assertStringContainsString('final class DTOSServiceProvider', $contents);
    }

    // -----------------------------------------------------------------------
    // 14. DTOException has named constructors
    // -----------------------------------------------------------------------

    /**
     * Verify DTOException provides both named constructors.
     */
    public function test_dto_exception_has_named_constructors(): void
    {
        $file = self::SRC_DIR . '/Exceptions/DTOException.php';
        $this->assertFileExists($file);

        $contents = file_get_contents($file);

        $this->assertStringContainsString('public static function invalidCast(', $contents);
        $this->assertStringContainsString('public static function invalidJson(', $contents);
        $this->assertStringContainsString('final class DTOException', $contents);
    }

    // -----------------------------------------------------------------------
    // 15. DTOCast implements CastsAttributes
    // -----------------------------------------------------------------------

    /**
     * Verify DTOCast implements Laravel's CastsAttributes interface.
     */
    public function test_dto_cast_implements_casts_attributes(): void
    {
        $file = self::SRC_DIR . '/Casts/DTOCast.php';
        $this->assertFileExists($file);

        $contents = file_get_contents($file);

        $this->assertStringContainsString('implements CastsAttributes', $contents);
        $this->assertStringContainsString('final class DTOCast', $contents);
    }

    // -----------------------------------------------------------------------
    // 16. Contracts are complete
    // -----------------------------------------------------------------------

    /**
     * Verify all interface contracts have the required method signatures.
     */
    public function test_contracts_are_complete(): void
    {
        // FromRequestDTO
        $fromRequestFile = self::SRC_DIR . '/Contracts/FromRequestDTO.php';
        $this->assertFileExists($fromRequestFile);
        $this->assertStringContainsString(
            'public static function fromRequest(Request $request, bool $validate = true): static',
            file_get_contents($fromRequestFile)
        );

        // ValidatableDTO
        $validatableFile = self::SRC_DIR . '/Contracts/ValidatableDTO.php';
        $this->assertFileExists($validatableFile);
        $this->assertStringContainsString(
            'public static function rules(): array',
            file_get_contents($validatableFile)
        );
        $this->assertStringContainsString(
            'public static function rulesFor(string $action): array',
            file_get_contents($validatableFile)
        );

        // ValidationAttribute
        $validationAttrFile = self::SRC_DIR . '/Contracts/ValidationAttribute.php';
        $this->assertFileExists($validationAttrFile);
        $this->assertStringContainsString(
            'public function ruleKey(): string',
            file_get_contents($validationAttrFile)
        );
    }

    // -----------------------------------------------------------------------
    // 17. DtoMetadataResolver is internal
    // -----------------------------------------------------------------------

    /**
     * Verify DtoMetadataResolver is a final internal class.
     */
    public function test_dto_metadata_resolver_is_final_internal(): void
    {
        $file = self::SRC_DIR . '/Support/DtoMetadataResolver.php';
        $this->assertFileExists($file);

        $contents = file_get_contents($file);

        $this->assertStringContainsString('final class DtoMetadataResolver', $contents);
        $this->assertStringContainsString('@internal', $contents);
    }

    // -----------------------------------------------------------------------
    // 18. OpenApiSchemaGenerator is internal
    // -----------------------------------------------------------------------

    /**
     * Verify OpenApiSchemaGenerator is a final internal class.
     */
    public function test_open_api_schema_generator_is_final_internal(): void
    {
        $file = self::SRC_DIR . '/Support/OpenApiSchemaGenerator.php';
        $this->assertFileExists($file);

        $contents = file_get_contents($file);

        $this->assertStringContainsString('final class OpenApiSchemaGenerator', $contents);
        $this->assertStringContainsString('@internal', $contents);
    }

    // -----------------------------------------------------------------------
    // 19. All attributes have #[Attribute] declaration
    // -----------------------------------------------------------------------

    /**
     * Every class in the Attributes namespace must use the #[Attribute] declaration.
     */
    public function test_attribute_classes_have_attribute_declaration(): void
    {
        $files = $this->getSourceFiles();

        foreach ($files as $file) {
            if (! str_contains($file, '/Attributes/')) {
                continue;
            }

            $contents = file_get_contents($file);

            if (! str_contains($contents, 'class ')) {
                continue;
            }

            $this->assertMatchesRegularExpression(
                '/#\[Attribute/',
                $contents,
                "Attribute class in {$file} must have an #[Attribute] declaration."
            );
        }
    }

    // -----------------------------------------------------------------------
    // 20. Deprecated annotation present on with() method
    // -----------------------------------------------------------------------

    /**
     * Verify the with() method has the #[Deprecated] PHP 8.5 attribute.
     */
    public function test_with_method_has_deprecated_attribute(): void
    {
        $file = self::SRC_DIR . '/DataTransferObject.php';
        $this->assertFileExists($file);

        $contents = file_get_contents($file);

        $this->assertStringContainsString('#[\Deprecated', $contents);
        $this->assertStringContainsString('since: \'1.1.0\'', $contents);
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    /**
     * Extract the class name from token array.
     *
     * @param non-empty-string $file
     * @param list<int|string|array{int, string, int}> $tokens
     */
    private function extractClassName(string $file, array $tokens): string
    {
        $foundClass = false;

        foreach ($tokens as $token) {
            if (is_array($token)) {
                if ($token[1] === 'class' || $token[1] === 'interface' || $token[1] === 'trait') {
                    $foundClass = true;

                    continue;
                }

                if ($foundClass && $token[0] === T_STRING) {
                    return $token[1];
                }
            }
        }

        return '';
    }

    /**
     * Determine if a class is final, abstract, a trait, or an interface.
     *
     * @param list<int|string|array{int, string, int}> $tokens
     * @return string One of: 'final', 'abstract', 'trait', 'interface', 'class'
     */
    private function getClassType(array $tokens): string
    {
        foreach ($tokens as $token) {
            if (is_array($token)) {
                if ($token[1] === 'final') {
                    return 'final';
                }

                if ($token[1] === 'abstract') {
                    return 'abstract';
                }

                if ($token[1] === 'trait') {
                    return 'trait';
                }

                if ($token[1] === 'interface') {
                    return 'interface';
                }

                if ($token[1] === 'class') {
                    return 'class';
                }
            }
        }

        return 'unknown';
    }
}
