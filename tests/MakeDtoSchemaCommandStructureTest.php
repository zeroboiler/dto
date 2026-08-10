<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Tests;

use LogicException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionException;
use ZeroBoiler\DTO\Console\Commands\MakeDtoSchemaCommand;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;
use ZeroBoiler\DTO\Tests\Fixtures\EmptyDTO;
use ZeroBoiler\DTO\Tests\Fixtures\MinimalDTO;

/**
 * Tests for the MakeDtoSchemaCommand artisan command.
 *
 * Verifies command instantiation, option defaults, and class validation
 * without actually executing the command (requires Laravel console).
 *
 * @covers \ZeroBoiler\DTO\Console\Commands\MakeDtoSchemaCommand
 */
final class MakeDtoSchemaCommandTest extends TestCase
{
    // -------------------------------------------------------------------
    // Command class structure
    // -------------------------------------------------------------------

    public function testCommandIsFinal(): void
    {
        $ref = new ReflectionClass(MakeDtoSchemaCommand::class);

        $this->assertTrue($ref->isFinal());
    }

    public function testCommandHasCorrectSignature(): void
    {
        $command = new MakeDtoSchemaCommand;

        $ref = new ReflectionClass($command);
        $prop = $ref->getProperty('signature');

        $this->assertSame(
            'zeroboiler:dto-schema {class : The DTO class FQN}
        {--output= : Output directory for the generated JSON schema file}
        {--with-components : Include component schemas for nested DTOs}
        {--json : Output as JSON string to stdout (alias for --print)}
        {--print : Print the schema to stdout instead of writing to a file}',
            $prop->getValue($command),
        );
    }

    public function testCommandHasDescription(): void
    {
        $command = new MakeDtoSchemaCommand;

        $ref = new ReflectionClass($command);
        $prop = $ref->getProperty('description');

        $this->assertNotEmpty($prop->getValue($command));
    }

    public function testCommandHandleMethodExists(): void
    {
        $ref = new ReflectionClass(MakeDtoSchemaCommand::class);

        $this->assertTrue($ref->hasMethod('handle'));
        $method = $ref->getMethod('handle');
        $this->assertSame('handle', $method->getName());
        $this->assertSame('int', $method->getReturnType()?->getName());
        $this->assertTrue($method->isPublic());
    }

    public function testCommandHandleHasOverrideAttribute(): void
    {
        $method = new \ReflectionMethod(MakeDtoSchemaCommand::class, 'handle');
        $attrs = $method->getAttributes();

        $hasOverride = false;
        foreach ($attrs as $attr) {
            if ($attr->getName() === 'Override') {
                $hasOverride = true;
                break;
            }
        }

        $this->assertTrue($hasOverride, 'handle() method should have #[Override] attribute');
    }

    // -------------------------------------------------------------------
    // Command options (reflection-based verification)
    // -------------------------------------------------------------------

    public function testSignatureIncludesJsonOption(): void
    {
        $command = new MakeDtoSchemaCommand;
        $ref = new ReflectionClass($command);
        $signature = $ref->getProperty('signature')->getValue($command);

        $this->assertStringContainsString('--json', $signature);
        $this->assertStringContainsString('--print', $signature);
        $this->assertStringContainsString('--with-components', $signature);
        $this->assertStringContainsString('--output=', $signature);
    }

    // -------------------------------------------------------------------
    // Integration: verify command references valid classes
    // -------------------------------------------------------------------

    public function testCommandReferencesValidSchemaGenerator(): void
    {
        // Verify the command's code uses OpenApiSchemaGenerator::generateWithComponents
        // by checking the source file contains the expected class reference
        $ref = new ReflectionClass(MakeDtoSchemaCommand::class);
        $filename = $ref->getFileName();

        $this->assertIsString($filename);
        $this->assertFileExists($filename);

        $content = file_get_contents($filename);
        $this->assertStringContainsString('OpenApiSchemaGenerator::generate', $content);
        $this->assertStringContainsString('OpenApiSchemaGenerator::generateWithComponents', $content);
        $this->assertStringContainsString('LogicException', $content);
        $this->assertStringContainsString('ReflectionException', $content);
    }
}
