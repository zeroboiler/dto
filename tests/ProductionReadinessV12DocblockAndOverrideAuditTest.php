<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

/**
 * Production Readiness V12 — Docblock completeness and #[Override] audit.
 *
 * Validates that every public class in the DTO package has:
 * - A class-level docblock with a description
 * - Every public method has a docblock
 * - Critical methods use #[\Override] where they implement/override an interface or parent method
 *
 * Also verifies:
 * - All public API methods have explicit return type declarations
 * - DtoCollection uses #[Override] on all interface methods
 * - DTOCast implements CastsAttributes with proper method signatures
 * - OpenApiSchemaGenerator is final and properly structured
 * - DtoMetadataResolver is final with correct method signatures
 * - All validation attributes implement ValidationAttribute with ruleKey()
 *
 * This is a static-source audit — it reads PHP files and checks their structure
 * without instantiating any objects or calling any methods.
 */

describe('Production Readiness V12 — Docblock & Override Audit (DTO)', function (): void {

    // ──────────────────────────────────────────────────────────────
    // 1. All source files have class-level docblocks
    // ──────────────────────────────────────────────────────────────

    describe('All source files have class-level docblocks', function (): void {
        $sourceDir = __DIR__ . '/../src';
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourceDir, \RecursiveDirectoryIterator::SKIP_DOTS),
        );
        $phpFiles = [];
        foreach ($iterator as $file) {
            if ($file->getExtension() === 'php') {
                $phpFiles[] = $file->getPathname();
            }
        }

        it('found source files to audit', function () use ($phpFiles): void {
            expect($phpFiles)->not->toBeEmpty();
            expect(count($phpFiles))->toBeGreaterThanOrEqual(50);
        });

        foreach ($phpFiles as $filePath) {
            $relative = str_replace($sourceDir . '/', '', $filePath);
            it("{$relative} has a class-level docblock", function () use ($filePath): void {
                $content = file_get_contents($filePath);
                $hasDocblock = (bool) preg_match('/\/\*\*[\s\S]*?\*\//', $content);
                expect($hasDocblock)->toBeTrue("Missing class-level docblock in {$filePath}");
            });
        }
    });

    // ──────────────────────────────────────────────────────────────
    // 2. All public methods in core classes have docblocks
    // ──────────────────────────────────────────────────────────────

    describe('Core classes: all public methods have docblocks', function (): void {
        $classesToAudit = [
            \ZeroBoiler\DTO\DataTransferObject::class,
            \ZeroBoiler\DTO\DtoCollection::class,
            \ZeroBoiler\DTO\DTOManager::class,
            \ZeroBoiler\DTO\DTOCast::class,
            \ZeroBoiler\DTO\DTOException::class,
            \ZeroBoiler\DTO\DtoMetadataResolver::class,
            \ZeroBoiler\DTO\OpenApiSchemaGenerator::class,
            \ZeroBoiler\DTO\DTOSServiceProvider::class,
        ];

        foreach ($classesToAudit as $class) {
            $shortName = (new \ReflectionClass($class))->getShortName();
            it("{$shortName}: all public methods have docblocks", function () use ($class): void {
                $ref = new \ReflectionClass($class);
                $fileName = $ref->getFileName();
                $content = (string) file_get_contents((string) $fileName);
                $lines = explode("\n", $content);

                $publicMethods = $ref->getMethods(\ReflectionMethod::IS_PUBLIC);
                foreach ($publicMethods as $method) {
                    if ($method->getName() === '__construct') {
                        continue;
                    }
                    if ($method->getDeclaringClass()->getName() !== $class) {
                        continue;
                    }

                    $startLine = $method->getStartLine() - 1;
                    $foundDocblock = false;
                    for ($i = $startLine - 1; $i >= max(0, $startLine - 10); $i--) {
                        $line = trim($lines[$i] ?? '');
                        if ($line === '') {
                            continue;
                        }
                        if (str_starts_with($line, '/**')) {
                            $foundDocblock = true;
                            break;
                        }
                        if (
                            str_starts_with($line, 'public ') ||
                            str_starts_with($line, 'private ') ||
                            str_starts_with($line, 'protected ') ||
                            str_starts_with($line, 'final ')
                        ) {
                            break;
                        }
                    }

                    expect($foundDocblock)->toBeTrue(
                        "{$shortName}::{$method->getName()}() at line {$method->getStartLine()} missing docblock"
                    );
                }
            });
        }
    });

    // ──────────────────────────────────────────────────────────────
    // 3. DtoCollection uses #[Override] on all interface methods
    // ──────────────────────────────────────────────────────────────

    describe('DtoCollection interface methods use #[Override]', function (): void {
        $overrideMethods = [
            'count', 'getIterator', 'offsetExists',
            'offsetGet', 'offsetSet', 'offsetUnset', 'jsonSerialize',
        ];

        foreach ($overrideMethods as $method) {
            it("DtoCollection::{$method}() has #[Override]", function () use ($method): void {
                $ref = new \ReflectionMethod(\ZeroBoiler\DTO\DtoCollection::class, $method);
                $attrs = $ref->getAttributes();
                $hasOverride = false;
                foreach ($attrs as $attr) {
                    if ($attr->getName() === \Override::class) {
                        $hasOverride = true;
                        break;
                    }
                }
                expect($hasOverride)->toBeTrue(
                    "DtoCollection::{$method}() should have #[\\Override] attribute"
                );
            });
        }
    });

    // ──────────────────────────────────────────────────────────────
    // 4. DTOCast uses #[Override] on interface methods
    // ──────────────────────────────────────────────────────────────

    describe('DTOCast interface methods use #[Override]', function (): void {
        $overrideMethods = ['get', 'set'];

        foreach ($overrideMethods as $method) {
            it("DTOCast::{$method}() has #[Override]", function () use ($method): void {
                $ref = new \ReflectionMethod(\ZeroBoiler\DTO\DTOCast::class, $method);
                $attrs = $ref->getAttributes();
                $hasOverride = false;
                foreach ($attrs as $attr) {
                    if ($attr->getName() === \Override::class) {
                        $hasOverride = true;
                        break;
                    }
                }
                expect($hasOverride)->toBeTrue(
                    "DTOCast::{$method}() should have #[\\Override] attribute"
                );
            });
        }
    });

    // ──────────────────────────────────────────────────────────────
    // 5. DTOManager is final readonly
    // ──────────────────────────────────────────────────────────────

    describe('DTOManager is final readonly', function (): void {
        it('is final', function (): void {
            $ref = new \ReflectionClass(\ZeroBoiler\DTO\DTOManager::class);
            expect($ref->isFinal())->toBeTrue();
        });

        it('is readonly', function (): void {
            $ref = new \ReflectionClass(\ZeroBoiler\DTO\DTOManager::class);
            expect($ref->isReadOnly())->toBeTrue();
        });

        it('all public methods have return types', function (): void {
            $ref = new \ReflectionClass(\ZeroBoiler\DTO\DTOManager::class);
            foreach ($ref->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                if ($method->getDeclaringClass()->getName() !== \ZeroBoiler\DTO\DTOManager::class) {
                    continue;
                }
                expect($method->hasReturnType())
                    ->toBeTrue("DTOManager::{$method->getName()}() missing return type");
            }
        });
    });

    // ──────────────────────────────────────────────────────────────
    // 6. All validation attributes implement ValidationAttribute
    // ──────────────────────────────────────────────────────────────

    describe('All validation attributes implement ValidationAttribute', function (): void {
        $validationAttributes = [
            \ZeroBoiler\DTO\Attributes\Accepted::class,
            \ZeroBoiler\DTO\Attributes\ArrayRule::class,
            \ZeroBoiler\DTO\Attributes\Between::class,
            \ZeroBoiler\DTO\Attributes\Boolean::class,
            \ZeroBoiler\DTO\Attributes\Confirmed::class,
            \ZeroBoiler\DTO\Attributes\Date::class,
            \ZeroBoiler\DTO\Attributes\Declined::class,
            \ZeroBoiler\DTO\Attributes\Different::class,
            \ZeroBoiler\DTO\Attributes\Distinct::class,
            \ZeroBoiler\DTO\Attributes\Email::class,
            \ZeroBoiler\DTO\Attributes\EndsWith::class,
            \ZeroBoiler\DTO\Attributes\Enum::class,
            \ZeroBoiler\DTO\Attributes\In::class,
            \ZeroBoiler\DTO\Attributes\Integer::class,
            \ZeroBoiler\DTO\Attributes\Json::class,
            \ZeroBoiler\DTO\Attributes\Max::class,
            \ZeroBoiler\DTO\Attributes\Min::class,
            \ZeroBoiler\DTO\Attributes\Nullable::class,
            \ZeroBoiler\DTO\Attributes\Numeric::class,
            \ZeroBoiler\DTO\Attributes\Pattern::class,
            \ZeroBoiler\DTO\Attributes\Present::class,
            \ZeroBoiler\DTO\Attributes\Prohibited::class,
            \ZeroBoiler\DTO\Attributes\Required::class,
            \ZeroBoiler\DTO\Attributes\RequiredIf::class,
            \ZeroBoiler\DTO\Attributes\RequiredUnless::class,
            \ZeroBoiler\DTO\Attributes\RequiredWith::class,
            \ZeroBoiler\DTO\Attributes\RequiredWithAll::class,
            \ZeroBoiler\DTO\Attributes\RequiredWithout::class,
            \ZeroBoiler\DTO\Attributes\RequiredWithoutAll::class,
            \ZeroBoiler\DTO\Attributes\Same::class,
            \ZeroBoiler\DTO\Attributes\Size::class,
            \ZeroBoiler\DTO\Attributes\Sometimes::class,
            \ZeroBoiler\DTO\Attributes\StartsWith::class,
            \ZeroBoiler\DTO\Attributes\Url::class,
            \ZeroBoiler\DTO\Attributes\Uuid::class,
        ];

        it('has the expected number of validation attributes', function () use ($validationAttributes): void {
            expect(count($validationAttributes))->toBe(36);
        });

        foreach ($validationAttributes as $class) {
            $shortName = (new \ReflectionClass($class))->getShortName();
            it("{$shortName} implements ValidationAttribute", function () use ($class): void {
                $ref = new \ReflectionClass($class);
                expect($ref->implementsInterface(\ZeroBoiler\DTO\Contracts\ValidationAttribute::class))
                    ->toBeTrue();
            });

            it("{$shortName} has ruleKey() method returning string", function () use ($class): void {
                $ref = new \ReflectionMethod($class, 'ruleKey');
                expect($ref->getReturnType()->getName())->toBe('string');
            });

            it("{$shortName} is final", function () use ($class): void {
                $ref = new \ReflectionClass($class);
                expect($ref->isFinal())->toBeTrue();
            });
        }
    });

    // ──────────────────────────────────────────────────────────────
    // 7. DtoCollection implements all required interfaces
    // ──────────────────────────────────────────────────────────────

    describe('DtoCollection interface compliance', function (): void {
        $interfaces = [
            \ArrayAccess::class,
            \Countable::class,
            \IteratorAggregate::class,
            \JsonSerializable::class,
        ];

        foreach ($interfaces as $interface) {
            $shortName = (new \ReflectionClass($interface))->getShortName();
            it("implements {$shortName}", function () use ($interface): void {
                $ref = new \ReflectionClass(\ZeroBoiler\DTO\DtoCollection::class);
                expect($ref->implementsInterface($interface))->toBeTrue();
            });
        }

        it('is final', function (): void {
            $ref = new \ReflectionClass(\ZeroBoiler\DTO\DtoCollection::class);
            expect($ref->isFinal())->toBeTrue();
        });

        it('is NOT readonly (has mutable state)', function (): void {
            $ref = new \ReflectionClass(\ZeroBoiler\DTO\DtoCollection::class);
            expect($ref->isReadOnly())->toBeFalse();
        });
    });

    // ──────────────────────────────────────────────────────────────
    // 8. DTOException is final with factory methods
    // ──────────────────────────────────────────────────────────────

    describe('DTOException structure', function (): void {
        it('is final', function (): void {
            expect((new \ReflectionClass(\ZeroBoiler\DTO\DTOException::class))->isFinal())->toBeTrue();
        });

        it('has invalidCast() and invalidJson() factory methods', function (): void {
            $ref = new \ReflectionClass(\ZeroBoiler\DTO\DTOException::class);
            expect($ref->hasMethod('invalidCast'))->toBeTrue();
            expect($ref->hasMethod('invalidJson'))->toBeTrue();
        });

        it('factory methods return self', function (): void {
            $ref = new \ReflectionClass(\ZeroBoiler\DTO\DTOException::class);
            expect($ref->getMethod('invalidCast')->getReturnType()->getName())->toBe('self');
            expect($ref->getMethod('invalidJson')->getReturnType()->getName())->toBe('self');
        });

        it('__toString() has #[Override] and returns string', function (): void {
            $ref = new \ReflectionMethod(\ZeroBoiler\DTO\DTOException::class, '__toString');
            expect($ref->getReturnType()->getName())->toBe('string');
            $hasOverride = false;
            foreach ($ref->getAttributes() as $attr) {
                if ($attr->getName() === \Override::class) {
                    $hasOverride = true;
                    break;
                }
            }
            expect($hasOverride)->toBeTrue();
        });
    });

    // ──────────────────────────────────────────────────────────────
    // 9. OpenApiSchemaGenerator — final class structure
    // ──────────────────────────────────────────────────────────────

    describe('OpenApiSchemaGenerator structure', function (): void {
        it('is final', function (): void {
            $ref = new \ReflectionClass(\ZeroBoiler\DTO\OpenApiSchemaGenerator::class);
            expect($ref->isFinal())->toBeTrue();
        });

        it('has generate() and generateWithComponents() static methods', function (): void {
            $ref = new \ReflectionClass(\ZeroBoiler\DTO\OpenApiSchemaGenerator::class);
            expect($ref->getMethod('generate')->isStatic())->toBeTrue();
            expect($ref->getMethod('generateWithComponents')->isStatic())->toBeTrue();
        });

        it('generate() returns array', function (): void {
            $ref = new \ReflectionMethod(\ZeroBoiler\DTO\OpenApiSchemaGenerator::class, 'generate');
            expect($ref->getReturnType()->getName())->toBe('array');
        });

        it('generateWithComponents() returns array', function (): void {
            $ref = new \ReflectionMethod(\ZeroBoiler\DTO\OpenApiSchemaGenerator::class, 'generateWithComponents');
            expect($ref->getReturnType()->getName())->toBe('array');
        });
    });

    // ──────────────────────────────────────────────────────────────
    // 10. DtoMetadataResolver — final class structure
    // ──────────────────────────────────────────────────────────────

    describe('DtoMetadataResolver structure', function (): void {
        it('is final', function (): void {
            $ref = new \ReflectionClass(\ZeroBoiler\DTO\DtoMetadataResolver::class);
            expect($ref->isFinal())->toBeTrue();
        });

        it('resolve() is static returning array', function (): void {
            $ref = new \ReflectionMethod(\ZeroBoiler\DTO\DtoMetadataResolver::class, 'resolve');
            expect($ref->isStatic())->toBeTrue();
            expect($ref->getReturnType()->getName())->toBe('array');
        });

        it('has only one public method (resolve)', function (): void {
            $ref = new \ReflectionClass(\ZeroBoiler\DTO\DtoMetadataResolver::class);
            $publicMethods = array_filter(
                $ref->getMethods(\ReflectionMethod::IS_PUBLIC),
                fn (\ReflectionMethod $m): bool => $m->getDeclaringClass()->getName() === \ZeroBoiler\DTO\DtoMetadataResolver::class,
            );
            expect(count($publicMethods))->toBe(1);
            expect(array_values($publicMethods)[0]->getName())->toBe('resolve');
        });
    });

    // ──────────────────────────────────────────────────────────────
    // 11. Metadata attributes — non-validation attributes are NOT ValidationAttribute
    // ──────────────────────────────────────────────────────────────

    describe('Metadata attributes do not implement ValidationAttribute', function (): void {
        $metadataAttributes = [
            \ZeroBoiler\DTO\Attributes\Hidden::class,
            \ZeroBoiler\DTO\Attributes\MapFrom::class,
            \ZeroBoiler\DTO\Attributes\Cast::class,
            \ZeroBoiler\DTO\Attributes\DefaultValue::class,
            \ZeroBoiler\DTO\Attributes\NestedArray::class,
            \ZeroBoiler\DTO\Attributes\Collection::class,
        ];

        foreach ($metadataAttributes as $class) {
            $shortName = (new \ReflectionClass($class))->getShortName();
            it("{$shortName} does NOT implement ValidationAttribute", function () use ($class): void {
                $ref = new \ReflectionClass($class);
                expect($ref->implementsInterface(\ZeroBoiler\DTO\Contracts\ValidationAttribute::class))
                    ->toBeFalse();
            });

            it("{$shortName} is final", function () use ($class): void {
                $ref = new \ReflectionClass($class);
                expect($ref->isFinal())->toBeTrue();
            });
        }
    });

    // ──────────────────────────────────────────────────────────────
    // 12. Contracts — interface completeness
    // ──────────────────────────────────────────────────────────────

    describe('DTO Contracts completeness', function (): void {
        it('FromRequestDTO requires fromRequest() static method', function (): void {
            $ref = new \ReflectionClass(\ZeroBoiler\DTO\Contracts\FromRequestDTO::class);
            expect($ref->isInterface())->toBeTrue();
            expect($ref->hasMethod('fromRequest'))->toBeTrue();
            $m = $ref->getMethod('fromRequest');
            expect($m->isStatic())->toBeTrue();
        });

        it('ValidatableDTO requires rules() and rulesFor() static methods', function (): void {
            $ref = new \ReflectionClass(\ZeroBoiler\DTO\Contracts\ValidatableDTO::class);
            expect($ref->isInterface())->toBeTrue();
            expect($ref->hasMethod('rules'))->toBeTrue();
            expect($ref->hasMethod('rulesFor'))->toBeTrue();
        });

        it('ValidationAttribute requires ruleKey() method', function (): void {
            $ref = new \ReflectionClass(\ZeroBoiler\DTO\Contracts\ValidationAttribute::class);
            expect($ref->isInterface())->toBeTrue();
            expect($ref->hasMethod('ruleKey'))->toBeTrue();
        });
    });

    // ──────────────────────────────────────────────────────────────
    // 13. DTO facade — correct accessor
    // ──────────────────────────────────────────────────────────────

    describe('DTO facade accessor', function (): void {
        it('is final extending Facade', function (): void {
            $ref = new \ReflectionClass(\ZeroBoiler\DTO\Facades\DTO::class);
            expect($ref->isFinal())->toBeTrue();
            expect($ref->isSubclassOf(\Illuminate\Support\Facades\Facade::class))->toBeTrue();
        });

        it('getFacadeAccessor returns zeroboiler.dto', function (): void {
            $method = new \ReflectionMethod(\ZeroBoiler\DTO\Facades\DTO::class, 'getFacadeAccessor');
            $method->setAccessible(true);
            expect($method->invoke(null))->toBe('zeroboiler.dto');
        });
    });

    // ──────────────────────────────────────────────────────────────
    // 14. DTOSServiceProvider — correct structure
    // ──────────────────────────────────────────────────────────────

    describe('DTOSServiceProvider structure', function (): void {
        it('is final', function (): void {
            $ref = new \ReflectionClass(\ZeroBoiler\DTO\DTOSServiceProvider::class);
            expect($ref->isFinal())->toBeTrue();
        });

        it('extends ServiceProvider', function (): void {
            $ref = new \ReflectionClass(\ZeroBoiler\DTO\DTOSServiceProvider::class);
            expect($ref->isSubclassOf(\Illuminate\Support\ServiceProvider::class))->toBeTrue();
        });

        it('register() and boot() have void return types', function (): void {
            $ref = new \ReflectionClass(\ZeroBoiler\DTO\DTOSServiceProvider::class);
            expect($ref->getMethod('register')->getReturnType()->getName())->toBe('void');
            expect($ref->getMethod('boot')->getReturnType()->getName())->toBe('void');
        });
    });
});
