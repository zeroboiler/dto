<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\Console\Commands\MakeDtoSchemaCommand;
use ZeroBoiler\DTO\Console\Commands\MakeDtoTestCommand;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;

describe('Console Commands (unit-level)', function (): void {
    describe('MakeDtoSchemaCommand', function (): void {
        it('has correct signature and description', function (): void {
            $command = new MakeDtoSchemaCommand;

            expect($command->getName())->toBe('zeroboiler:dto-schema')
                ->and($command->getDescription())->toBe('Generate OpenAPI schema from a ZeroBoiler DTO class');
        });

        it('command class is final', function (): void {
            $reflection = new ReflectionClass(MakeDtoSchemaCommand::class);

            expect($reflection->isFinal())->toBeTrue();
        });

        it('has class argument and --json option in signature', function (): void {
            $command = new MakeDtoSchemaCommand;
            $definition = $command->getDefinition();

            expect($definition->hasArgument('class'))->toBeTrue()
                ->and($definition->getArgument('class')->isRequired())->toBeTrue()
                ->and($definition->hasOption('json'))->toBeTrue();
        });

        it('handle method has int return type', function (): void {
            $reflection = new ReflectionMethod(MakeDtoSchemaCommand::class, 'handle');

            expect($reflection->getReturnType())->not->toBeNull()
                ->and((string) $reflection->getReturnType())->toBe('int');
        });
    });

    describe('MakeDtoTestCommand', function (): void {
        it('has correct signature and description', function (): void {
            $command = new MakeDtoTestCommand;

            expect($command->getName())->toBe('zeroboiler:dto-test')
                ->and($command->getDescription())->toBe('Generate Pest tests for a ZeroBoiler DTO class');
        });

        it('command class is final', function (): void {
            $reflection = new ReflectionClass(MakeDtoTestCommand::class);

            expect($reflection->isFinal())->toBeTrue();
        });

        it('has class argument and --dir option in signature', function (): void {
            $command = new MakeDtoTestCommand;
            $definition = $command->getDefinition();

            expect($definition->hasArgument('class'))->toBeTrue()
                ->and($definition->getArgument('class')->isRequired())->toBeTrue()
                ->and($definition->hasOption('dir'))->toBeTrue();
        });

        it('generateFakeData returns empty array for class without constructor', function (): void {
            $command = new MakeDtoTestCommand;
            $reflection = new ReflectionMethod($command, 'generateFakeData');

            // stdClass has no constructor parameters
            $result = $reflection->invoke($command, 'stdClass');

            expect($result)->toBe([]);
        });

        it('generateFakeData extracts required constructor parameters', function (): void {
            $command = new MakeDtoTestCommand;
            $reflection = new ReflectionMethod($command, 'generateFakeData');

            $result = $reflection->invoke($command, CreateUserDTO::class);

            // email and name have no PHP-level defaults — included
            // status has DefaultValue attribute but no PHP default — included
            // tags/phone/password have PHP defaults — skipped
            expect($result)->toBeArray()
                ->and($result)->toHaveKey('email')
                ->and($result)->toHaveKey('name')
                ->and($result)->toHaveKey('status')
                // tags, phone, password have actual PHP defaults
                ->and($result)->not->toHaveKey('tags')
                ->and($result)->not->toHaveKey('phone')
                ->and($result)->not->toHaveKey('password');
        });

        it('fakeValueForType generates name-based hints', function (): void {
            $command = new MakeDtoTestCommand;
            $reflection = new ReflectionMethod($command, 'fakeValueForType');

            // Get a real ReflectionNamedType from a closure parameter
            $testFn = eval('return function (string $email_field): void {};');
            $param = new ReflectionParameter($testFn, 0);
            $type = $param->getType();

            // Name-based hints are checked before type — so the ReflectionType
            // just needs to exist; the name match takes priority.

            // email
            $emailValue = $reflection->invoke($command, $type, 'user_email');
            expect($emailValue)->toBe('test@example.com');

            // name
            $nameValue = $reflection->invoke($command, $type, 'full_name');
            expect($nameValue)->toBe('Test Full_name');

            // url
            $urlValue = $reflection->invoke($command, $type, 'profile_url');
            expect($urlValue)->toBe('https://example.com');

            // uuid
            $uuidValue = $reflection->invoke($command, $type, 'tracking_uuid');
            expect($uuidValue)->toBe('550e8400-e29b-41d4-a716-446655440000');

            // phone
            $phoneValue = $reflection->invoke($command, $type, 'contact_phone');
            expect($phoneValue)->toBe('+1234567890');

            // password
            $passwordValue = $reflection->invoke($command, $type, 'user_password');
            expect($passwordValue)->toBe('password123');

            // date
            $dateValue = $reflection->invoke($command, $type, 'created_date');
            expect($dateValue)->toBe('2024-01-15 10:30:00');
        });

        it('formatDataAsPhp formats array as PHP literal', function (): void {
            $command = new MakeDtoTestCommand;
            $reflection = new ReflectionMethod($command, 'formatDataAsPhp');

            $result = $reflection->invoke($command, []);
            expect($result)->toBe('[]');

            $result = $reflection->invoke($command, ['name' => 'Test', 'count' => 5, 'active' => true, 'extra' => null]);
            expect($result)->toContain("'name' => 'Test'")
                ->and($result)->toContain("'count' => 5")
                ->and($result)->toContain("'active' => true")
                ->and($result)->toContain("'extra' => null")
                ->and($result)->toStartWith('[')
                ->and($result)->toEndWith(']');
        });
    });
});
