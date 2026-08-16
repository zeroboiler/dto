<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;

describe('DTO __debugInfo Production Readiness V35', function (): void {
    it('DataTransferObject __debugInfo returns toArray output', function (): void {
        $dto = new class(['name' => 'Alice', 'age' => 30]) extends DataTransferObject {
            public function __construct(
                public readonly string $name = '',
                public readonly int $age = 0,
            ) {}
        };

        $debug = $dto->__debugInfo();

        expect($debug)->toBe($dto->toArray());
        expect($debug)->toHaveKeys(['name', 'age']);
        expect($debug['name'])->toBe('Alice');
        expect($debug['age'])->toBe(30);
    });

    it('DataTransferObject __debugInfo excludes hidden properties', function (): void {
        $dto = new class(['email' => 'test@example.com', 'password' => 'secret123']) extends DataTransferObject {
            public function __construct(
                public readonly string $email = '',
                #[\ZeroBoiler\DTO\Attributes\Hidden]
                public readonly string $password = '',
            ) {}
        };

        $debug = $dto->__debugInfo();

        expect($debug)->toHaveKey('email');
        expect($debug)->not->toHaveKey('password');
    });

    it('DataTransferObject __debugInfo returns array type', function (): void {
        $dto = new class([]) extends DataTransferObject {
            public function __construct(
                public readonly ?string $value = null,
            ) {}
        };

        $debug = $dto->__debugInfo();

        expect($debug)->toBeArray();
    });

    it('DtoCollection __debugInfo returns count and truncated items', function (): void {
        $items = [];
        for ($i = 1; $i <= 10; $i++) {
            $items[] = new class(['id' => $i, 'name' => "Item {$i}"]) extends DataTransferObject {
                public function __construct(
                    public readonly int $id = 0,
                    public readonly string $name = '',
                ) {}
            };
        }

        $collection = new DtoCollection($items);
        $debug = $collection->__debugInfo();

        expect($debug)->toBeArray();
        expect($debug)->toHaveKeys(['count', 'items']);
        expect($debug['count'])->toBe(10);
        expect($debug['items'])->toHaveCount(3); // truncated to first 3
        expect($debug['items'][0]['id'])->toBe(1);
        expect($debug['items'][2]['id'])->toBe(3);
    });

    it('DtoCollection __debugInfo with empty collection', function (): void {
        $collection = new DtoCollection;

        $debug = $collection->__debugInfo();

        expect($debug['count'])->toBe(0);
        expect($debug['items'])->toBe([]);
    });

    it('DtoCollection __debugInfo with single item', function (): void {
        $dto = new class(['id' => 42]) extends DataTransferObject {
            public function __construct(
                public readonly int $id = 0,
            ) {}
        };

        $collection = new DtoCollection([$dto]);
        $debug = $collection->__debugInfo();

        expect($debug['count'])->toBe(1);
        expect($debug['items'])->toHaveCount(1);
        expect($debug['items'][0]['id'])->toBe(42);
    });

    it('DtoCollection __debugInfo items are arrays not DTO objects', function (): void {
        $dto = new class(['key' => 'value']) extends DataTransferObject {
            public function __construct(
                public readonly string $key = '',
            ) {}
        };

        $collection = new DtoCollection([$dto]);
        $debug = $collection->__debugInfo();

        // Items should be serialized arrays, not DTO instances
        expect($debug['items'][0])->toBeArray();
        expect($debug['items'][0])->not->toBeInstanceOf(DataTransferObject::class);
    });

    it('DtoCollection __debugInfo respects hidden on nested DTOs', function (): void {
        $dto = new class(['public' => 'visible', 'secret' => 'hidden']) extends DataTransferObject {
            public function __construct(
                public readonly string $public = '',
                #[\ZeroBoiler\DTO\Attributes\Hidden]
                public readonly string $secret = '',
            ) {}
        };

        $collection = new DtoCollection([$dto]);
        $debug = $collection->__debugInfo();

        expect($debug['items'][0])->toHaveKey('public');
        expect($debug['items'][0])->not->toHaveKey('secret');
    });
});
