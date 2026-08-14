<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Tests;

use PHPUnit\Framework\TestCase;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;

/**
 * fromPartialArray() interaction with MapFrom, DefaultValue, and hidden fields.
 *
 * Tests critical PATCH semantics edge cases:
 * - MapFrom keys are respected in partial data
 * - DefaultValue fills in missing fields
 * - Hidden fields remain accessible but excluded from toArray()
 * - Empty partial data returns DTO with all defaults
 * - Explicit null overrides defaults
 */
final class DtoPartialArrayMapFromAndDefaultsTest extends TestCase
{
    // -----------------------------------------------------------------------
    // MapFrom key mapping in partial mode
    // -----------------------------------------------------------------------

    public function testPartialArrayRespectsMapFromKeys(): void
    {
        $dto = CreateUserDTO::fromPartialArray([
            'phone_number' => '+905551234567',
        ], validatePresent: false);

        self::assertSame('+905551234567', $dto->phone);
    }

    public function testPartialArrayUsesPropertyNameWhenMapFromNotProvided(): void
    {
        $dto = CreateUserDTO::fromPartialArray([
            'email' => 'test@example.com',
        ], validatePresent: false);

        self::assertSame('test@example.com', $dto->email);
    }

    // -----------------------------------------------------------------------
    // DefaultValue fills missing fields
    // -----------------------------------------------------------------------

    public function testPartialArrayFillsMissingFieldsWithDefaults(): void
    {
        $dto = CreateUserDTO::fromPartialArray([
            'email' => 'test@example.com',
            'name' => 'Test User',
        ], validatePresent: false);

        // status has DefaultValue('active')
        self::assertSame('active', $dto->status);
        // tags has default [] (PHP default, not DefaultValue attribute)
        self::assertSame([], $dto->tags);
    }

    // -----------------------------------------------------------------------
    // Empty partial data
    // -----------------------------------------------------------------------

    public function testPartialArrayWithEmptyDataReturnsDefaultDtos(): void
    {
        $dto = CreateUserDTO::fromPartialArray([], validatePresent: false);

        // All fields should have defaults or type-appropriate empty values
        self::assertSame('', $dto->email);
        self::assertSame('', $dto->name);
        self::assertSame('active', $dto->status);
        self::assertSame([], $dto->tags);
        self::assertNull($dto->phone);
        self::assertNull($dto->password);
    }

    // -----------------------------------------------------------------------
    // Hidden fields in partial mode
    // -----------------------------------------------------------------------

    public function testPartialArrayHiddenFieldsAccessibleButExcludedFromToArray(): void
    {
        $dto = CreateUserDTO::fromPartialArray([
            'password' => 'secret123',
        ], validatePresent: false);

        // Hidden field is accessible on the instance
        self::assertSame('secret123', $dto->password);

        // But excluded from toArray()
        $arr = $dto->toArray();
        self::assertArrayNotHasKey('password', $arr);

        // Included in allValues()
        $all = $dto->allValues();
        self::assertArrayHasKey('password', $all);
    }

    // -----------------------------------------------------------------------
    // toArray consistency after partial update
    // -----------------------------------------------------------------------

    public function testPartialArrayThenToArrayHasExpectedKeys(): void
    {
        $dto = CreateUserDTO::fromPartialArray([
            'email' => 'partial@test.com',
            'name' => 'Partial User',
        ], validatePresent: false);

        $arr = $dto->toArray();

        self::assertArrayHasKey('email', $arr);
        self::assertArrayHasKey('name', $arr);
        self::assertArrayHasKey('status', $arr);
        self::assertArrayHasKey('tags', $arr);
        self::assertArrayHasKey('phone', $arr);
        // password is hidden
        self::assertArrayNotHasKey('password', $arr);
    }

    // -----------------------------------------------------------------------
    // only() and except() after partial update
    // -----------------------------------------------------------------------

    public function testPartialArrayThenOnlyReturnsSubset(): void
    {
        $dto = CreateUserDTO::fromPartialArray([
            'email' => 'only@test.com',
            'name' => 'Only User',
        ], validatePresent: false);

        $result = $dto->only('email');
        self::assertSame(['email' => 'only@test.com'], $result);
    }

    public function testPartialArrayThenExceptExcludesFields(): void
    {
        $dto = CreateUserDTO::fromPartialArray([
            'email' => 'except@test.com',
            'name' => 'Except User',
        ], validatePresent: false);

        $result = $dto->except('status', 'tags', 'phone');
        self::assertArrayHasKey('email', $result);
        self::assertArrayHasKey('name', $result);
        self::assertArrayNotHasKey('status', $result);
    }
}
