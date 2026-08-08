<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace Tests\DTO;

use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;
use ZeroBoiler\DTO\Attributes\Email;
use ZeroBoiler\DTO\Attributes\Hidden;
use ZeroBoiler\DTO\Attributes\Max;
use ZeroBoiler\DTO\Attributes\Min;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Support\OpenApiSchemaGenerator;

/** @internal */
final class SimpleContactDTO extends \ZeroBoiler\DTO\DataTransferObject
{
    public function __construct(
        #[Required, Email, Max(100)]
        public readonly string $email,

        #[Required, Min(1), Max(100)]
        public readonly string $name,
    ) {}
}

/**
 * @covers \ZeroBoiler\DTO\DtoCollection
 *
 * Tests for append() and merge() methods — immutable collection operations.
 */
final class DtoCollectionAppendAndMergeTest extends TestCase
{
    private SimpleContactDTO $dto1;
    private SimpleContactDTO $dto2;
    private SimpleContactDTO $dto3;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dto1 = new SimpleContactDTO(
            email: 'alice@example.com',
            name: 'Alice',
        );

        $this->dto2 = new SimpleContactDTO(
            email: 'bob@example.com',
            name: 'Bob',
        );

        $this->dto3 = new SimpleContactDTO(
            email: 'charlie@example.com',
            name: 'Charlie',
        );
    }

    // ── append() tests ──────────────────────────────────────────

    public function test_append_returns_new_collection(): void
    {
        $original = DtoCollection::make([$this->dto1]);
        $appended = $original->append($this->dto2);

        $this->assertNotSame($original, $appended);
        $this->assertCount(1, $original);
        $this->assertCount(2, $appended);
    }

    public function test_append_preserves_original_items(): void
    {
        $original = DtoCollection::make([$this->dto1]);
        $appended = $original->append($this->dto2);

        $this->assertSame($this->dto1, $original->items()[0]);
        $this->assertSame($this->dto1, $appended->items()[0]);
        $this->assertSame($this->dto2, $appended->items()[1]);
    }

    public function test_append_chainable(): void
    {
        $result = DtoCollection::make([$this->dto1])
            ->append($this->dto2)
            ->append($this->dto3);

        $this->assertCount(3, $result);
        $this->assertSame('alice@example.com', $result->items()[0]->email);
        $this->assertSame('bob@example.com', $result->items()[1]->email);
        $this->assertSame('charlie@example.com', $result->items()[2]->email);
    }

    public function test_append_to_empty_collection(): void
    {
        $empty = DtoCollection::make([]);
        $result = $empty->append($this->dto1);

        $this->assertCount(0, $empty);
        $this->assertCount(1, $result);
    }

    public function test_append_serialization_includes_appended_item(): void
    {
        $original = DtoCollection::make([$this->dto1]);
        $appended = $original->append($this->dto2);

        $array = $appended->toArray();

        $this->assertCount(2, $array);
        $this->assertSame('alice@example.com', $array[0]['email']);
        $this->assertSame('bob@example.com', $array[1]['email']);
    }

    public function test_append_json_serialization(): void
    {
        $result = DtoCollection::make([$this->dto1])
            ->append($this->dto2);

        $json = json_encode($result);

        $this->assertNotFalse($json);
        $decoded = json_decode($json, true);
        $this->assertCount(2, $decoded);
    }

    // ── merge() tests ───────────────────────────────────────────

    public function test_merge_combines_two_collections(): void
    {
        $a = DtoCollection::make([$this->dto1, $this->dto2]);
        $b = DtoCollection::make([$this->dto3]);

        $merged = $a->merge($b);

        $this->assertNotSame($a, $merged);
        $this->assertNotSame($b, $merged);
        $this->assertCount(2, $a);
        $this->assertCount(1, $b);
        $this->assertCount(3, $merged);
    }

    public function test_merge_preserves_item_order(): void
    {
        $a = DtoCollection::make([$this->dto1]);
        $b = DtoCollection::make([$this->dto2, $this->dto3]);

        $merged = $a->merge($b);

        $this->assertSame('alice@example.com', $merged->items()[0]->email);
        $this->assertSame('bob@example.com', $merged->items()[1]->email);
        $this->assertSame('charlie@example.com', $merged->items()[2]->email);
    }

    public function test_merge_with_empty_collection(): void
    {
        $nonEmpty = DtoCollection::make([$this->dto1]);
        $empty = DtoCollection::make([]);

        $mergedLeft = $nonEmpty->merge($empty);
        $mergedRight = $empty->merge($nonEmpty);

        $this->assertCount(1, $mergedLeft);
        $this->assertCount(1, $mergedRight);
        $this->assertSame('alice@example.com', $mergedLeft->items()[0]->email);
        $this->assertSame('alice@example.com', $mergedRight->items()[0]->email);
    }

    public function test_merge_empty_with_empty(): void
    {
        $a = DtoCollection::make([]);
        $b = DtoCollection::make([]);

        $merged = $a->merge($b);

        $this->assertCount(0, $merged);
        $this->assertTrue($merged->isEmpty());
    }

    public function test_merge_does_not_mutate_originals(): void
    {
        $a = DtoCollection::make([$this->dto1]);
        $b = DtoCollection::make([$this->dto2]);

        $merged = $a->merge($b);

        $this->assertCount(1, $a);
        $this->assertCount(1, $b);
        $this->assertCount(2, $merged);
    }

    public function test_merge_serialization(): void
    {
        $a = DtoCollection::make([$this->dto1]);
        $b = DtoCollection::make([$this->dto2]);

        $merged = $a->merge($b);
        $array = $merged->toArray();

        $this->assertCount(2, $array);
        $this->assertSame('Alice', $array[0]['name']);
        $this->assertSame('Bob', $array[1]['name']);
    }

    // ── Combined append + merge ─────────────────────────────────

    public function test_append_then_merge(): void
    {
        $a = DtoCollection::make([$this->dto1]);
        $b = DtoCollection::make([$this->dto2]);

        $result = $a->append($this->dto3)->merge($b);

        $this->assertCount(3, $result);
        $this->assertCount(1, $a); // original unchanged
    }

    // ── clone semantics ─────────────────────────────────────────

    public function test_append_does_not_affect_original_push(): void
    {
        $original = DtoCollection::make([$this->dto1]);
        $appended = $original->append($this->dto2);

        // Push to original — appended should not be affected
        $original->push($this->dto3);

        $this->assertCount(2, $original); // dto1 + dto3
        $this->assertCount(2, $appended); // dto1 + dto2
    }

    public function test_original_mutation_does_not_affect_appended(): void
    {
        $original = DtoCollection::make([$this->dto1, $this->dto2]);
        $appended = $original->append($this->dto3);

        // Remove from original
        unset($original[0]);

        // Appended collection still has all 3 items
        $this->assertCount(3, $appended);
        $this->assertSame('alice@example.com', $appended->items()[0]->email);
    }
}
