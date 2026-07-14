<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\Attributes\ArrayRule;
use ZeroBoiler\DTO\Attributes\Between;
use ZeroBoiler\DTO\Attributes\Confirmed;
use ZeroBoiler\DTO\Attributes\Declined;
use ZeroBoiler\DTO\Attributes\Different;
use ZeroBoiler\DTO\Attributes\EndsWith;
use ZeroBoiler\DTO\Attributes\Present;
use ZeroBoiler\DTO\Attributes\Prohibited;
use ZeroBoiler\DTO\Attributes\Same;
use ZeroBoiler\DTO\Attributes\StartsWith;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\Tests\Fixtures\AddressDTO;
use ZeroBoiler\DTO\Tests\Fixtures\OrderDTO;
use ZeroBoiler\DTO\Tests\Fixtures\OrderItemDTO;

// -----------------------------------------------------------------------
// Nested DTO hydration (#117)
// -----------------------------------------------------------------------

describe('Nested DTO hydration (#117)', function (): void {
    it('hydrates a nested DTO from array', function (): void {
        $dto = OrderDTO::fromArray([
            'orderNumber' => 'ORD-001',
            'shippingAddress' => [
                'street' => '123 Main St',
                'city' => 'Istanbul',
            ],
        ], validate: false);

        expect($dto->shippingAddress)
            ->toBeInstanceOf(AddressDTO::class)
            ->and($dto->shippingAddress->street)->toBe('123 Main St')
            ->and($dto->shippingAddress->city)->toBe('Istanbul');
    });

    it('passes through an already-hydrated DTO instance', function (): void {
        $address = new AddressDTO('456 Oak Ave', 'Ankara');

        $dto = OrderDTO::fromArray([
            'orderNumber' => 'ORD-002',
            'shippingAddress' => $address,
        ], validate: false);

        expect($dto->shippingAddress)
            ->toBeInstanceOf(AddressDTO::class)
            ->and($dto->shippingAddress)->toBe($address);
    });

    it('hydrates array of nested DTOs via #[NestedArray]', function (): void {
        $dto = OrderDTO::fromArray([
            'orderNumber' => 'ORD-003',
            'shippingAddress' => ['street' => '1 St', 'city' => 'Izmir'],
            'items' => [
                ['productName' => 'Widget', 'price' => 29.99, 'quantity' => 2],
                ['productName' => 'Gadget', 'price' => 15.50],
            ],
        ], validate: false);

        expect($dto->items)
            ->toHaveCount(2)
            ->and($dto->items[0])->toBeInstanceOf(OrderItemDTO::class)
            ->and($dto->items[0]->productName)->toBe('Widget')
            ->and($dto->items[0]->quantity)->toBe(2)
            ->and($dto->items[1])->toBeInstanceOf(OrderItemDTO::class)
            ->and($dto->items[1]->quantity)->toBe(1); // default
    });

    it('passes through already-hydrated DTOs in nested arrays', function (): void {
        $item = new OrderItemDTO('Existing', 9.99, 5);

        $dto = OrderDTO::fromArray([
            'orderNumber' => 'ORD-004',
            'shippingAddress' => ['street' => '1 St', 'city' => 'Bursa'],
            'items' => [$item],
        ], validate: false);

        expect($dto->items[0])->toBe($item);
    });

    it('serializes nested DTOs in toArray()', function (): void {
        $dto = OrderDTO::fromArray([
            'orderNumber' => 'ORD-005',
            'shippingAddress' => ['street' => '1 St', 'city' => 'Istanbul'],
            'items' => [
                ['productName' => 'Widget', 'price' => 10.0],
            ],
        ], validate: false);

        $array = $dto->toArray();

        expect($array['shippingAddress'])
            ->toBeArray()
            ->and($array['shippingAddress']['city'])->toBe('Istanbul')
            ->and($array['items'])->toBeArray()
            ->and($array['items'][0])->toBeArray()
            ->and($array['items'][0]['productName'])->toBe('Widget');
    });

    it('serializes nested DTOs in toJson()', function (): void {
        $dto = OrderDTO::fromArray([
            'orderNumber' => 'ORD-006',
            'shippingAddress' => ['street' => '1 St', 'city' => 'Istanbul'],
        ], validate: false);

        $json = $dto->toJson();
        $decoded = json_decode($json, true);

        expect($decoded['shippingAddress'])
            ->toBeArray()
            ->and($decoded['shippingAddress']['street'])->toBe('1 St');
    });

    it('throws on invalid nested DTO data', function (): void {
        OrderDTO::fromArray([
            'orderNumber' => 'ORD-007',
            'shippingAddress' => 'not-an-array', // invalid
        ], validate: false);
    })->throws(InvalidArgumentException::class, 'Cannot hydrate string into nested DTO');

    it('throws on invalid element in nested array', function (): void {
        OrderDTO::fromArray([
            'orderNumber' => 'ORD-008',
            'shippingAddress' => ['street' => '1 St', 'city' => 'X'],
            'items' => [
                ['productName' => 'Valid', 'price' => 1.0],
                'not-an-array', // invalid
            ],
        ], validate: false);
    })->throws(InvalidArgumentException::class, 'Cannot hydrate element at index 1');

    it('supports nested DTOs in fromPartialArray', function (): void {
        $dto = OrderDTO::fromPartialArray([
            'orderNumber' => 'ORD-PATCH',
            'shippingAddress' => ['street' => 'Patched St', 'city' => 'Patched City'],
        ], validatePresent: false);

        expect($dto->shippingAddress)
            ->toBeInstanceOf(AddressDTO::class)
            ->and($dto->shippingAddress->street)->toBe('Patched St');
    });

    it('supports equality check with nested DTOs', function (): void {
        $data = [
            'orderNumber' => 'ORD-009',
            'shippingAddress' => ['street' => '1 St', 'city' => 'Istanbul'],
        ];

        $dto1 = OrderDTO::fromArray($data, validate: false);
        $dto2 = OrderDTO::fromArray($data, validate: false);

        expect($dto1->equals($dto2))->toBeTrue();
    });

    it('supports with() overrides on nested DTO fields', function (): void {
        $dto = OrderDTO::fromArray([
            'orderNumber' => 'ORD-010',
            'shippingAddress' => ['street' => 'Old St', 'city' => 'Old City'],
        ], validate: false);

        $updated = $dto->with([
            'shippingAddress' => ['street' => 'New St', 'city' => 'New City'],
        ], validate: false);

        expect($updated->shippingAddress)
            ->toBeInstanceOf(AddressDTO::class)
            ->and($updated->shippingAddress->street)->toBe('New St');
    });
});

// -----------------------------------------------------------------------
// Fixture for new validation attributes
// -----------------------------------------------------------------------

class ValidationTestDTO extends DataTransferObject
{
    public function __construct(
        #[Confirmed]
        public readonly string $password,

        #[Different('password')]
        public readonly string $username,

        #[Same('password')]
        public readonly string $passwordRepeat,

        #[Between(1, 100)]
        public readonly int $quantity,

        #[StartsWith('https://')]
        public readonly string $url,

        #[EndsWith('.com')]
        public readonly string $domain,

        #[Present]
        public readonly ?string $apiKey,

        #[Declined]
        public readonly mixed $optOut,

        #[Prohibited]
        public readonly ?string $internalField,
    ) {}
}

// -----------------------------------------------------------------------
// New validation attributes (#92)
// -----------------------------------------------------------------------

describe('New validation attributes (#92)', function (): void {
    it('generates confirmed rule', function (): void {
        $rules = ValidationTestDTO::rules();

        expect($rules['password'])->toContain('confirmed');
    });

    it('generates different rule', function (): void {
        $rules = ValidationTestDTO::rules();

        expect($rules['username'])->toContain('different:password');
    });

    it('generates same rule', function (): void {
        $rules = ValidationTestDTO::rules();

        expect($rules['passwordRepeat'])->toContain('same:password');
    });

    it('generates between rule', function (): void {
        $rules = ValidationTestDTO::rules();

        expect($rules['quantity'])->toContain('between:1,100');
    });

    it('generates starts_with rule', function (): void {
        $rules = ValidationTestDTO::rules();

        expect($rules['url'])->toContain('starts_with:https://');
    });

    it('generates ends_with rule', function (): void {
        $rules = ValidationTestDTO::rules();

        expect($rules['domain'])->toContain('ends_with:.com');
    });

    it('generates present rule', function (): void {
        $rules = ValidationTestDTO::rules();

        expect($rules['apiKey'])->toContain('present');
    });

    it('generates declined rule', function (): void {
        $rules = ValidationTestDTO::rules();

        expect($rules['optOut'])->toContain('declined');
    });

    it('generates prohibited rule', function (): void {
        $rules = ValidationTestDTO::rules();

        expect($rules['internalField'])->toContain('prohibited');
    });
});

// -----------------------------------------------------------------------
// StartsWith with multiple values
// -----------------------------------------------------------------------

class MultiPrefixDTO extends DataTransferObject
{
    public function __construct(
        #[StartsWith(['+90', '+1', '+44'])]
        public readonly string $phone,

        #[EndsWith(['@gmail.com', '@outlook.com'])]
        public readonly string $email,
    ) {}
}

describe('StartsWith/EndsWith with multiple values', function (): void {
    it('generates starts_with with comma-separated values', function (): void {
        $rules = MultiPrefixDTO::rules();

        expect($rules['phone'])->toContain('starts_with:+90,+1,+44');
    });

    it('generates ends_with with comma-separated values', function (): void {
        $rules = MultiPrefixDTO::rules();

        expect($rules['email'])->toContain('ends_with:@gmail.com,@outlook.com');
    });
});

// -----------------------------------------------------------------------
// ArrayRule with min/max
// -----------------------------------------------------------------------

class ArrayRuleDTO extends DataTransferObject
{
    public function __construct(
        #[ArrayRule]
        public readonly array $tags,

        #[ArrayRule(min: 1)]
        public readonly array $required,

        #[ArrayRule(min: 1, max: 5)]
        public readonly array $bounded,
    ) {}
}

describe('ArrayRule attribute', function (): void {
    it('generates array rule without bounds', function (): void {
        $rules = ArrayRuleDTO::rules();

        expect($rules['tags'])->toContain('array');
        expect($rules['tags'])->not->toContain('min:0');
    });

    it('generates array rule with min only', function (): void {
        $rules = ArrayRuleDTO::rules();

        expect($rules['required'])->toContain('array');
        expect($rules['required'])->toContain('min:1');
    });

    it('generates array rule with min and max', function (): void {
        $rules = ArrayRuleDTO::rules();

        expect($rules['bounded'])->toContain('array');
        expect($rules['bounded'])->toContain('min:1');
        expect($rules['bounded'])->toContain('max:5');
    });
});

// -----------------------------------------------------------------------
// Union type rule inference (#61)
// -----------------------------------------------------------------------

use ZeroBoiler\DTO\Tests\Fixtures\UnionTypeDTO;

describe('Union type rule inference (#61)', function (): void {
    it('infers rules for int|string union type', function (): void {
        $rules = UnionTypeDTO::rules();

        // int|string should produce 'integer' rule
        expect($rules['identifier'])->toContain('integer');
    });

    it('infers rules for int|float|string union type', function (): void {
        $rules = OrderDTO::rules();

        // int|float|string should produce 'integer' and 'numeric'
        expect($rules['rawTotal'])->toContain('integer');
        expect($rules['rawTotal'])->toContain('numeric');
    });
});
