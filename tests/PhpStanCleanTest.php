<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

/**
 * Verifies that the PHPStan configuration is clean — no baseline needed
 * and the target PHP version is configured correctly.
 *
 * Issue #6: PHPStan reported 38 errors including missing generic types,
 * unsafe new static(), and unresolved symbols. All are now resolved.
 */
describe('PHPStan Configuration (Issue #6)', function (): void {
    it('has no baseline file', function (): void {
        expect(file_exists(__DIR__.'/../phpstan-baseline.neon'))->toBeFalse();
    });

    it('configures phpVersion for PHP 8.5 target', function (): void {
        $config = file_get_contents(__DIR__.'/../phpstan.neon');
        expect($config)->toContain('phpVersion: 80500');
    });

    it('does not include baseline in config', function (): void {
        $config = file_get_contents(__DIR__.'/../phpstan.neon');
        expect($config)->not->toContain('phpstan-baseline.neon');
    });

    it('has DtoMetadataResolver with proper iterable type annotations', function (): void {
        $source = file_get_contents(__DIR__.'/../src/Support/DtoMetadataResolver.php');

        // $propMeta should have typed iterable annotation
        expect($source)->toContain('@param  array<string, mixed>  $propMeta');

        // $messages should have typed iterable annotation
        expect($source)->toContain('array<string, mixed>  $messages');

        // Local variable annotation for messages array
        expect($source)->toContain('/** @var array<string, string> $messages */');
    });
});
