<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\Attributes\Accepted;
use ZeroBoiler\DTO\Attributes\Distinct;
use ZeroBoiler\DTO\Attributes\Email;
use ZeroBoiler\DTO\Attributes\Json;
use ZeroBoiler\DTO\Attributes\Nullable;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\Attributes\RequiredIf;
use ZeroBoiler\DTO\Attributes\RequiredUnless;
use ZeroBoiler\DTO\Attributes\RequiredWith;
use ZeroBoiler\DTO\Attributes\RequiredWithAll;
use ZeroBoiler\DTO\Attributes\RequiredWithout;
use ZeroBoiler\DTO\Attributes\RequiredWithoutAll;
use ZeroBoiler\DTO\Attributes\Size;
use ZeroBoiler\DTO\Attributes\Sometimes;
use ZeroBoiler\DTO\DataTransferObject;

describe('Issue #10: new validation attributes', function (): void {
    describe('simple attributes', function (): void {
        it('generates nullable rule', function (): void {
            $rules = NewAttributesDTO::rules();
            expect($rules['middleName'])->toContain('nullable');
        });

        it('generates sometimes rule', function (): void {
            $rules = NewAttributesDTO::rules();
            expect($rules['note'])->toContain('sometimes');
        });

        it('generates distinct rule', function (): void {
            $rules = NewAttributesDTO::rules();
            expect($rules['tags'])->toContain('distinct');
        });

        it('generates accepted rule', function (): void {
            $rules = NewAttributesDTO::rules();
            expect($rules['terms'])->toContain('accepted');
        });

        it('generates size rule', function (): void {
            $rules = NewAttributesDTO::rules();
            expect($rules['code'])->toContain('size:6');
        });

        it('generates json rule', function (): void {
            $rules = NewAttributesDTO::rules();
            expect($rules['payload'])->toContain('json');
        });
    });

    describe('conditional required attributes', function (): void {
        it('generates required_if rule', function (): void {
            $rules = NewAttributesDTO::rules();
            expect($rules['publishedAt'])->toContain('required_if:status,published');
        });

        it('generates required_unless rule', function (): void {
            $rules = NewAttributesDTO::rules();
            expect($rules['price'])->toContain('required_unless:type,free');
        });

        it('generates required_with rule', function (): void {
            $rules = NewAttributesDTO::rules();
            expect($rules['companyId'])->toContain('required_with:companyName');
        });

        it('generates required_with_all rule', function (): void {
            $rules = NewAttributesDTO::rules();
            expect($rules['billingId'])->toContain('required_with_all:billingAddress,shippingAddress');
        });

        it('generates required_without rule', function (): void {
            $rules = NewAttributesDTO::rules();
            expect($rules['phone'])->toContain('required_without:email');
        });

        it('generates required_without_all rule', function (): void {
            $rules = NewAttributesDTO::rules();
            expect($rules['username'])->toContain('required_without_all:email,phone');
        });
    });

    describe('required_with multiple fields', function (): void {
        it('accepts array of fields for RequiredWith', function (): void {
            $rules = MultiFieldDTO::rules();
            expect($rules['value'])->toContain('required_with:field1,field2');
        });

        it('accepts array of fields for RequiredWithout', function (): void {
            $rules = MultiFieldDTO::rules();
            expect($rules['fallback'])->toContain('required_without:a,b,c');
        });
    });
});

class NewAttributesDTO extends DataTransferObject
{
    public function __construct(
        #[Required, Email]
        public readonly string $email,

        #[Nullable]
        public readonly ?string $middleName = null,

        #[Sometimes]
        public readonly ?string $note = null,

        #[Distinct]
        public readonly array $tags = [],

        #[Accepted]
        public readonly mixed $terms = false,

        #[Size(6)]
        public readonly string $code = '000000',

        #[Json]
        public readonly ?string $payload = null,

        #[RequiredIf('status', 'published')]
        public readonly ?string $publishedAt = null,

        #[RequiredUnless('type', 'free')]
        public readonly ?string $price = null,

        #[RequiredWith('companyName')]
        public readonly ?string $companyId = null,

        #[RequiredWithAll(['billingAddress', 'shippingAddress'])]
        public readonly ?string $billingId = null,

        #[RequiredWithout('email')]
        public readonly ?string $phone = null,

        #[RequiredWithoutAll(['email', 'phone'])]
        public readonly ?string $username = null,
    ) {}
}

class MultiFieldDTO extends DataTransferObject
{
    public function __construct(
        #[RequiredWith(['field1', 'field2'])]
        public readonly ?string $value = null,

        #[RequiredWithout(['a', 'b', 'c'])]
        public readonly ?string $fallback = null,
    ) {}
}
