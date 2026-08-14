<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Tests;

use PHPUnit\Framework\TestCase;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Tests\Fixtures\CreateUserDTO;

/**
 * DtoCollection offsetUnset re-indexing and toArrayBy null-safety contract tests.
 *
 * Validates critical collection behaviors that can cause subtle bugs:
 * - offsetUnset() re-indexes the internal array (prevents gaps)
 * - toArrayBy() skips null key values (prevents PHP null-key coercion)
 * - toDictionary() skips null key values
 * - map() preserves key association
 * - Cloned collections are independent (append returns new instance)
 */
final class DtoCollectionOffsetAndReindexContractTest extends TestCase
{
    // -----------------------------------------------------------------------
    // offsetUnset re-indexing
    // -----------------------------------------------------------------------

    public function testOffsetUnsetReindexesInternalArray(): void
    {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'a@test.com',
            'name' => 'Alice',
        ]);
        $dto2 = CreateUserDTO::fromArray([
            'email' => 'b@test.com',
            'name' => 'Bob',
        ]);
        $dto3 = CreateUserDTO::fromArray([
            'email' => 'c@test.com',
            'name' => 'Charlie',
        ]);

        $col = new DtoCollection([$dto1, $dto2, $dto3]);

        // Remove middle element
        unset($col[1]);

        // After re-indexing, index 1 should now hold $dto3
        self::assertSame(2, $col->count());
        self::assertSame('c@test.com', $col[1]?->email);
        self::assertNull($col[2]); // Old index 2 no longer exists
    }

    public function testOffsetUnsetFirstElementShiftsRemaining(): void
    {
        $dto1 = CreateUserDTO::fromArray(['email' => 'a@test.com', 'name' => 'A']);
        $dto2 = CreateUserDTO::fromArray(['email' => 'b@test.com', 'name' => 'B']);

        $col = new DtoCollection([$dto1, $dto2]);
        unset($col[0]);

        self::assertSame(1, $col->count());
        self::assertSame('b@test.com', $col[0]?->email);
    }

    public function testOffsetUnsetLastElementPreservesOrder(): void
    {
        $dto1 = CreateUserDTO::fromArray(['email' => 'a@test.com', 'name' => 'A']);
        $dto2 = CreateUserDTO::fromArray(['email' => 'b@test.com', 'name' => 'B']);

        $col = new DtoCollection([$dto1, $dto2]);
        unset($col[1]);

        self::assertSame(1, $col->count());
        self::assertSame('a@test.com', $col[0]?->email);
    }

    // -----------------------------------------------------------------------
    // toArrayBy null key safety
    // -----------------------------------------------------------------------

    public function testToArrayBySkipsNullKeyValues(): void
    {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'a@test.com',
            'name' => 'Alice',
            'phone' => '123',
        ]);
        $dto2 = CreateUserDTO::fromArray([
            'email' => 'b@test.com',
            'name' => 'Bob',
            // phone is null (default)
        ]);

        $col = new DtoCollection([$dto1, $dto2]);
        $result = $col->toArrayBy('phone');

        // Only $dto1 should be present (phone='123')
        // $dto2 is skipped because phone=null
        self::assertCount(1, $result);
        self::assertArrayHasKey('123', $result);
    }

    // -----------------------------------------------------------------------
    // toDictionary null key safety
    // -----------------------------------------------------------------------

    public function testToDictionarySkipsNullKeyValues(): void
    {
        $dto1 = CreateUserDTO::fromArray([
            'email' => 'a@test.com',
            'name' => 'Alice',
        ]);
        $dto2 = CreateUserDTO::fromArray([
            'email' => 'b@test.com',
            'name' => 'Bob',
        ]);

        $col = new DtoCollection([$dto1, $dto2]);
        $result = $col->toDictionary('email', 'name');

        self::assertSame('Alice', $result['a@test.com']);
        self::assertSame('Bob', $result['b@test.com']);
    }

    // -----------------------------------------------------------------------
    // Append returns new independent collection
    // -----------------------------------------------------------------------

    public function testAppendReturnsNewIndependentCollection(): void
    {
        $dto1 = CreateUserDTO::fromArray(['email' => 'a@test.com', 'name' => 'A']);
        $dto2 = CreateUserDTO::fromArray(['email' => 'b@test.com', 'name' => 'B']);

        $col1 = new DtoCollection([$dto1]);
        $col2 = $col1->append($dto2);

        // col1 is unchanged
        self::assertSame(1, $col1->count());
        // col2 has both
        self::assertSame(2, $col2->count());
    }

    public function testPushMutatesOriginalCollection(): void
    {
        $dto1 = CreateUserDTO::fromArray(['email' => 'a@test.com', 'name' => 'A']);
        $dto2 = CreateUserDTO::fromArray(['email' => 'b@test.com', 'name' => 'B']);

        $col = new DtoCollection([$dto1]);
        $returned = $col->push($dto2);

        // push() mutates in place and returns same instance
        self::assertSame(2, $col->count());
        self::assertSame($col, $returned);
    }

    // -----------------------------------------------------------------------
    // Map preserves index association
    // -----------------------------------------------------------------------

    public function testMapPreservesIndexAssociation(): void
    {
        $dto1 = CreateUserDTO::fromArray(['email' => 'a@test.com', 'name' => 'Alice']);
        $dto2 = CreateUserDTO::fromArray(['email' => 'b@test.com', 'name' => 'Bob']);

        $col = new DtoCollection([$dto1, $dto2]);
        $emails = $col->map(fn (CreateUserDTO $dto, int $index): string => $dto->email);

        self::assertSame(['a@test.com', 'b@test.com'], $emails);
    }

    // -----------------------------------------------------------------------
    // Merge produces combined collection
    // -----------------------------------------------------------------------

    public function testMergeCombinesTwoCollections(): void
    {
        $dto1 = CreateUserDTO::fromArray(['email' => 'a@test.com', 'name' => 'A']);
        $dto2 = CreateUserDTO::fromArray(['email' => 'b@test.com', 'name' => 'B']);
        $dto3 = CreateUserDTO::fromArray(['email' => 'c@test.com', 'name' => 'C']);

        $col1 = new DtoCollection([$dto1]);
        $col2 = new DtoCollection([$dto2, $dto3]);
        $merged = $col1->merge($col2);

        // Neither original is mutated
        self::assertSame(1, $col1->count());
        self::assertSame(2, $col2->count());
        // Merged has all three
        self::assertSame(3, $merged->count());
    }
}
