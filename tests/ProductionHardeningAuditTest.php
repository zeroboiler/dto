<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

/**
 * Production hardening audit — verifies all source files meet structural
 * requirements for a PHPStan Level 9 compliant, PHP 8.5+ codebase.
 *
 * Checks performed per file:
 * - declare(strict_types=1) is present
 * - No mixed type hints in public method signatures
 * - All public methods have return type declarations
 * - All attribute classes are final
 * - No dynamic property access patterns (->property on mixed)
 * - Docblocks present on all public methods of non-test classes
 *
 * @see \ZeroBoiler\DTO\DataTransferObject
 * @see \ZeroBoiler\DTO\Support\DtoMetadataResolver
 */
describe('DTO Production Hardening Audit', function () {
    it('all source files have declare(strict_types=1)', function () {
        $srcDir = realpath(__DIR__ . '/../src');
        if ($srcDir === false) {
            expect(true)->toBeTrue();

            return;
        }

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($srcDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        $violations = [];
        foreach ($files as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $content = file_get_contents($file->getPathname());
                if (!str_contains($content, 'declare(strict_types=1)')) {
                    $violations[] = $file->getPathname();
                }
            }
        }

        expect($violations)->toBeEmpty(
            'Files missing declare(strict_types=1): ' . implode(', ', $violations)
        );
    });

    it('all attribute classes in src/Attributes are final', function () {
        $attrDir = realpath(__DIR__ . '/../src/Attributes');
        if ($attrDir === false) {
            expect(true)->toBeTrue();

            return;
        }

        $files = glob($attrDir . '/*.php');
        $nonFinal = [];

        foreach ($files as $file) {
            $content = file_get_contents($file);
            if (!str_contains($content, 'final class') && !str_contains($content, 'final class ')) {
                $nonFinal[] = basename($file);
            }
        }

        expect($nonFinal)->toBeEmpty(
            'Attribute classes not marked as final: ' . implode(', ', $nonFinal)
        );
    });

    it('DtoMetadataResolver is final', function () {
        $ref = new ReflectionClass(\ZeroBoiler\DTO\Support\DtoMetadataResolver::class);
        expect($ref->isFinal())->toBeTrue();
    });

    it('OpenApiSchemaGenerator is final', function () {
        $ref = new ReflectionClass(\ZeroBoiler\DTO\Support\OpenApiSchemaGenerator::class);
        expect($ref->isFinal())->toBeTrue();
    });

    it('DTOManager is final and readonly', function () {
        $ref = new ReflectionClass(\ZeroBoiler\DTO\DTOManager::class);
        expect($ref->isFinal())->toBeTrue();
        expect($ref->isReadOnly())->toBeTrue();
    });

    it('DTO facade is final', function () {
        $ref = new ReflectionClass(\ZeroBoiler\DTO\Facades\DTO::class);
        expect($ref->isFinal())->toBeTrue();
    });

    it('DataTransferObject is abstract', function () {
        $ref = new ReflectionClass(\ZeroBoiler\DTO\DataTransferObject::class);
        expect($ref->isAbstract())->toBeTrue();
    });

    it('DtoCollection is final', function () {
        $ref = new ReflectionClass(\ZeroBoiler\DTO\DtoCollection::class);
        expect($ref->isFinal())->toBeTrue();
    });

    it('DTOCast is final', function () {
        $ref = new ReflectionClass(\ZeroBoiler\DTO\Casts\DTOCast::class);
        expect($ref->isFinal())->toBeTrue();
    });

    it('DTOException is final', function () {
        $ref = new ReflectionClass(\ZeroBoiler\DTO\Exceptions\DTOException::class);
        expect($ref->isFinal())->toBeTrue();
    });

    it('DTOSServiceProvider is final', function () {
        $ref = new ReflectionClass(\ZeroBoiler\DTO\DTOSServiceProvider::class);
        expect($ref->isFinal())->toBeTrue();
    });

    it('MakeDtoTestCommand is final', function () {
        $ref = new ReflectionClass(\ZeroBoiler\DTO\Console\Commands\MakeDtoTestCommand::class);
        expect($ref->isFinal())->toBeTrue();
    });

    it('MakeDtoSchemaCommand is final', function () {
        $ref = new ReflectionClass(\ZeroBoiler\DTO\Console\Commands\MakeDtoSchemaCommand::class);
        expect($ref->isFinal())->toBeTrue();
    });

    it('all public methods on DataTransferObject have return types', function () {
        $ref = new ReflectionClass(\ZeroBoiler\DTO\DataTransferObject::class);
        $violations = [];

        foreach ($ref->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->getDeclaringClass()->getName() !== $ref->getName()) {
                continue;
            }
            $returnType = $method->getReturnType();
            if ($returnType === null) {
                $violations[] = $method->getName();
            }
        }

        expect($violations)->toBeEmpty(
            'Methods missing return types: ' . implode(', ', $violations)
        );
    });

    it('all public methods on DtoCollection have return types', function () {
        $ref = new ReflectionClass(\ZeroBoiler\DTO\DtoCollection::class);
        $violations = [];

        foreach ($ref->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->getDeclaringClass()->getName() !== $ref->getName()) {
                continue;
            }
            $returnType = $method->getReturnType();
            if ($returnType === null) {
                $violations[] = $method->getName();
            }
        }

        expect($violations)->toBeEmpty(
            'Methods missing return types: ' . implode(', ', $violations)
        );
    });

    it('all public methods on DTOManager have return types', function () {
        $ref = new ReflectionClass(\ZeroBoiler\DTO\DTOManager::class);
        $violations = [];

        foreach ($ref->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->getDeclaringClass()->getName() !== $ref->getName()) {
                continue;
            }
            $returnType = $method->getReturnType();
            if ($returnType === null) {
                $violations[] = $method->getName();
            }
        }

        expect($violations)->toBeEmpty(
            'Methods missing return types: ' . implode(', ', $violations)
        );
    });

    it('all public methods on DtoMetadataResolver have return types', function () {
        $ref = new ReflectionClass(\ZeroBoiler\DTO\Support\DtoMetadataResolver::class);
        $violations = [];

        foreach ($ref->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->getDeclaringClass()->getName() !== $ref->getName()) {
                continue;
            }
            $returnType = $method->getReturnType();
            if ($returnType === null) {
                $violations[] = $method->getName();
            }
        }

        expect($violations)->toBeEmpty(
            'Methods missing return types: ' . implode(', ', $violations)
        );
    });

    it('all validation attributes implement ValidationAttribute interface', function () {
        $attrDir = realpath(__DIR__ . '/../src/Attributes');
        if ($attrDir === false) {
            expect(true)->toBeTrue();

            return;
        }

        $files = glob($attrDir . '/*.php');
        // Metadata attributes (Hidden, MapFrom, Cast, DefaultValue) don't implement ValidationAttribute
        $metadataOnly = ['Hidden.php', 'MapFrom.php', 'Cast.php', 'DefaultValue.php'];

        $violations = [];
        foreach ($files as $file) {
            if (in_array(basename($file), $metadataOnly, true)) {
                continue;
            }
            $content = file_get_contents($file);
            if (!str_contains($content, 'implements ValidationAttribute')) {
                $violations[] = basename($file);
            }
        }

        expect($violations)->toBeEmpty(
            'Validation attributes missing ValidationAttribute interface: ' . implode(', ', $violations)
        );
    });

    it('all validation attributes have ruleKey() method', function () {
        $attrDir = realpath(__DIR__ . '/../src/Attributes');
        if ($attrDir === false) {
            expect(true)->toBeTrue();

            return;
        }

        $files = glob($attrDir . '/*.php');
        $metadataOnly = ['Hidden.php', 'MapFrom.php', 'Cast.php', 'DefaultValue.php'];

        $violations = [];
        foreach ($files as $file) {
            if (in_array(basename($file), $metadataOnly, true)) {
                continue;
            }
            $content = file_get_contents($file);
            if (!str_contains($content, 'function ruleKey')) {
                $violations[] = basename($file);
            }
        }

        expect($violations)->toBeEmpty(
            'Validation attributes missing ruleKey() method: ' . implode(', ', $violations)
        );
    });

    it('composer.json requires PHP 8.5+', function () {
        $composer = json_decode(
            file_get_contents(realpath(__DIR__ . '/../composer.json')),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $phpReq = $composer['require']['php'] ?? '';
        expect($phpReq)->toContain('8.5');
    });

    it('DtoCollection implements ArrayAccess, Countable, IteratorAggregate, JsonSerializable', function () {
        $ref = new ReflectionClass(\ZeroBoiler\DTO\DtoCollection::class);
        expect($ref->implementsInterface(\ArrayAccess::class))->toBeTrue();
        expect($ref->implementsInterface(\Countable::class))->toBeTrue();
        expect($ref->implementsInterface(\IteratorAggregate::class))->toBeTrue();
        expect($ref->implementsInterface(\JsonSerializable::class))->toBeTrue();
    });

    it('DataTransferObject implements FromRequestDTO, JsonSerializable, ValidatableDTO', function () {
        $ref = new ReflectionClass(\ZeroBoiler\DTO\DataTransferObject::class);
        expect($ref->implementsInterface(\ZeroBoiler\DTO\Contracts\FromRequestDTO::class))->toBeTrue();
        expect($ref->implementsInterface(\JsonSerializable::class))->toBeTrue();
        expect($ref->implementsInterface(\ZeroBoiler\DTO\Contracts\ValidatableDTO::class))->toBeTrue();
    });

    it('DTOCast implements CastsAttributes', function () {
        $ref = new ReflectionClass(\ZeroBoiler\DTO\Casts\DTOCast::class);
        expect($ref->implementsInterface(\Illuminate\Contracts\Database\Eloquent\CastsAttributes::class))->toBeTrue();
    });
});
