<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\Attributes\Accepted;
use ZeroBoiler\DTO\Attributes\ArrayRule;
use ZeroBoiler\DTO\Attributes\Between;
use ZeroBoiler\DTO\Attributes\Boolean;
use ZeroBoiler\DTO\Attributes\Cast;
use ZeroBoiler\DTO\Attributes\Collection;
use ZeroBoiler\DTO\Attributes\Confirmed;
use ZeroBoiler\DTO\Attributes\Date;
use ZeroBoiler\DTO\Attributes\Declined;
use ZeroBoiler\DTO\Attributes\DefaultValue;
use ZeroBoiler\DTO\Attributes\Different;
use ZeroBoiler\DTO\Attributes\Distinct;
use ZeroBoiler\DTO\Attributes\Email;
use ZeroBoiler\DTO\Attributes\EndsWith;
use ZeroBoiler\DTO\Attributes\Enum;
use ZeroBoiler\DTO\Attributes\Hidden;
use ZeroBoiler\DTO\Attributes\In;
use ZeroBoiler\DTO\Attributes\Integer;
use ZeroBoiler\DTO\Attributes\Json;
use ZeroBoiler\DTO\Attributes\MapFrom;
use ZeroBoiler\DTO\Attributes\Max;
use ZeroBoiler\DTO\Attributes\Min;
use ZeroBoiler\DTO\Attributes\NestedArray;
use ZeroBoiler\DTO\Attributes\Nullable;
use ZeroBoiler\DTO\Attributes\Numeric;
use ZeroBoiler\DTO\Attributes\Pattern;
use ZeroBoiler\DTO\Attributes\Present;
use ZeroBoiler\DTO\Attributes\Prohibited;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\Attributes\RequiredIf;
use ZeroBoiler\DTO\Attributes\RequiredUnless;
use ZeroBoiler\DTO\Attributes\RequiredWith;
use ZeroBoiler\DTO\Attributes\RequiredWithAll;
use ZeroBoiler\DTO\Attributes\RequiredWithout;
use ZeroBoiler\DTO\Attributes\RequiredWithoutAll;
use ZeroBoiler\DTO\Attributes\Same;
use ZeroBoiler\DTO\Attributes\Size;
use ZeroBoiler\DTO\Attributes\Sometimes;
use ZeroBoiler\DTO\Attributes\StartsWith;
use ZeroBoiler\DTO\Attributes\Url;
use ZeroBoiler\DTO\Attributes\Uuid;
use ZeroBoiler\DTO\Contracts\ValidationAttribute;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Tests\Fixtures\OrderItemDTO;
use ZeroBoiler\DTO\Tests\Fixtures\RegistrationDTO;
use ZeroBoiler\DTO\Tests\Fixtures\TaskListDTO;

/**
 * Comprehensive attribute contract and integration tests.
 *
 * Verifies that every validation attribute:
 * - Is final
 * - Implements ValidationAttribute
 * - Returns correct ruleKey()
 * - Has properly typed constructor parameters
 *
 * Plus integration tests for RegistrationDTO and TaskListDTO fixtures.
 */
describe('Attribute Contract Compliance', function () {
    beforeEach(function () {
        DataTransferObject::flushMetadataCache();
    });

    afterEach(function () {
        DataTransferObject::flushMetadataCache();
    });

    // -----------------------------------------------------------------------
    // Section 1: Every validation attribute implements ValidationAttribute
    // -----------------------------------------------------------------------
    describe('all validation attributes implement ValidationAttribute', function () {
        $attributes = [
            Accepted::class,
            Boolean::class,
            Between::class,
            Confirmed::class,
            Declined::class,
            Different::class,
            Distinct::class,
            Email::class,
            EndsWith::class,
            In::class,
            Integer::class,
            Json::class,
            Max::class,
            Min::class,
            Nullable::class,
            Numeric::class,
            Pattern::class,
            Present::class,
            Prohibited::class,
            Required::class,
            RequiredIf::class,
            RequiredUnless::class,
            RequiredWith::class,
            RequiredWithAll::class,
            RequiredWithout::class,
            RequiredWithoutAll::class,
            Same::class,
            Size::class,
            Sometimes::class,
            StartsWith::class,
            Url::class,
            Uuid::class,
        ];

        test('every attribute implements ValidationAttribute', function () use ($attributes) {
            foreach ($attributes as $attrClass) {
                expect($attrClass)->toImplement(ValidationAttribute::class);
            }
        });

        test('every attribute is final', function () use ($attributes) {
            $reflection = new ReflectionClass(Required::class);
            expect($reflection->isFinal())->toBeTrue();
        });

        test('every attribute has ruleKey() returning non-empty string', function () use ($attributes) {
            foreach ($attributes as $attrClass) {
                $instance = $attrClass === Accepted::class
                    ? new Accepted
                    : new $attrClass;

                expect($instance->ruleKey())->toBeString();
                expect($instance->ruleKey())->not->toBeEmpty();
            }
        });
    });

    // -----------------------------------------------------------------------
    // Section 2: Non-validation attributes are also properly structured
    // -----------------------------------------------------------------------
    describe('non-validation attributes have proper structure', function () {
        test('Cast is final with typed constructor', function () {
            $cast = new Cast('integer');
            expect($cast->type)->toBe('integer');
        });

        test('DefaultValue is final and accepts mixed', function () {
            $dv = new DefaultValue('test');
            expect($dv->value)->toBe('test');

            $dvNull = new DefaultValue;
            expect($dvNull->value)->toBeNull();
        });

        test('Hidden is final', function () {
            expect((new ReflectionClass(Hidden::class))->isFinal())->toBeTrue();
        });

        test('MapFrom is final with typed constructor', function () {
            $mf = new MapFrom('source_key');
            expect($mf->key)->toBe('source_key');
        });

        test('NestedArray is final and implements ValidationAttribute', function () {
            expect(NestedArray::class)->toImplement(ValidationAttribute::class);
            expect((new ReflectionClass(NestedArray::class))->isFinal())->toBeTrue();
        });

        test('Collection is final and implements ValidationAttribute', function () {
            expect(Collection::class)->toImplement(ValidationAttribute::class);
            expect((new ReflectionClass(Collection::class))->isFinal())->toBeTrue();
        });

        test('Enum attribute has class-string BackedEnum param', function () {
            $enum = new Enum(\ZeroBoiler\Enums\Tests\Fixtures\UserStatus::class);
            expect($enum->enumClass)->toBe(\ZeroBoiler\Enums\Tests\Fixtures\UserStatus::class);
            expect($enum->ruleKey())->toBe('enum');
        });
    });

    // -----------------------------------------------------------------------
    // Section 3: Specific ruleKey values
    // -----------------------------------------------------------------------
    describe('ruleKey values are correct', function () {
        $expectedKeys = [
            Accepted::class => 'accepted',
            Boolean::class => 'boolean',
            Between::class => 'between',
            Confirmed::class => 'confirmed',
            Declined::class => 'declined',
            Different::class => 'different',
            Distinct::class => 'distinct',
            Email::class => 'email',
            EndsWith::class => 'ends_with',
            In::class => 'in',
            Integer::class => 'integer',
            Json::class => 'json',
            Max::class => 'max',
            Min::class => 'min',
            Nullable::class => 'nullable',
            Numeric::class => 'numeric',
            Pattern::class => 'regex',
            Present::class => 'present',
            Prohibited::class => 'prohibited',
            Required::class => 'required',
            RequiredIf::class => 'required_if',
            RequiredUnless::class => 'required_unless',
            RequiredWith::class => 'required_with',
            RequiredWithAll::class => 'required_with_all',
            RequiredWithout::class => 'required_without',
            RequiredWithoutAll::class => 'required_without_all',
            Same::class => 'same',
            Size::class => 'size',
            Sometimes::class => 'sometimes',
            StartsWith::class => 'starts_with',
            Url::class => 'url',
            Uuid::class => 'uuid',
            NestedArray::class => 'array',
            Collection::class => 'array',
        ];

        test('each attribute returns expected ruleKey', function () use ($expectedKeys) {
            foreach ($expectedKeys as $attrClass => $expectedKey) {
                $instance = new $attrClass;
                expect($instance->ruleKey())->toBe($expectedKey, "Failed for {$attrClass}");
            }
        });
    });

    // -----------------------------------------------------------------------
    // Section 4: RegistrationDTO integration tests
    // -----------------------------------------------------------------------
    describe('RegistrationDTO — conditional and comparison attributes', function () {
        it('creates with valid data', function () {
            $dto = RegistrationDTO::fromArray([
                'email' => 'user@example.com',
                'password' => 'secure123',
                'password_confirmation' => 'secure123',
                'termsAccepted' => true,
            ], validate: false);

            expect($dto->email)->toBe('user@example.com');
            expect($dto->password)->toBe('secure123');
            expect($dto->termsAccepted)->toBeTrue();
            expect($dto->marketingOptOut)->toBeFalse();
        });

        it('excludes hidden field from toArray', function () {
            $dto = RegistrationDTO::fromArray([
                'email' => 'user@example.com',
                'password' => 'secure123',
                'termsAccepted' => true,
                'ipAddress' => '192.168.1.1',
            ], validate: false);

            $arr = $dto->toArray();
            expect($arr)->not->toHaveKey('ipAddress');
            expect($arr)->toHaveKey('email');
        });

        it('includes hidden field in allValues', function () {
            $dto = RegistrationDTO::fromArray([
                'email' => 'user@example.com',
                'password' => 'secure123',
                'termsAccepted' => true,
                'ipAddress' => '192.168.1.1',
            ], validate: false);

            $all = $dto->allValues();
            expect($all)->toHaveKey('ipAddress');
            expect($all['ipAddress'])->toBe('192.168.1.1');
        });

        it('defaults optional fields correctly', function () {
            $dto = RegistrationDTO::fromArray([
                'email' => 'user@example.com',
                'password' => 'secure123',
                'termsAccepted' => true,
            ], validate: false);

            expect($dto->username)->toBeNull();
            expect($dto->referrerName)->toBeNull();
            expect($dto->phone)->toBeNull();
            expect($dto->referralCode)->toBeNull();
            expect($dto->marketingOptOut)->toBeFalse();
        });

        it('generates rules with Confirmed for password', function () {
            $rules = RegistrationDTO::rules();

            expect($rules)->toHaveKey('password');
            expect($rules['password'])->toContain('confirmed');
            expect($rules['password'])->toContain('required');
            expect($rules['password'])->toContain('min:8');
        });

        it('generates rules with Declined for marketing', function () {
            $rules = RegistrationDTO::rules();

            expect($rules)->toHaveKey('marketingOptOut');
            expect($rules['marketingOptOut'])->toContain('declined');
        });

        it('generates rules with Same for passwordRepeat', function () {
            $rules = RegistrationDTO::rules();

            expect($rules)->toHaveKey('passwordRepeat');
            expect($rules['passwordRepeat'])->toContain('same:password');
        });

        it('generates rules with Different for username vs email', function () {
            $rules = RegistrationDTO::rules();

            expect($rules)->toHaveKey('username');
            expect($rules['username'])->toContain('different:email');
        });

        it('generates rules with RequiredWith for referrerName', function () {
            $rules = RegistrationDTO::rules();

            expect($rules)->toHaveKey('referrerName');
            expect($rules['referrerName'])->toContain('required_with:referralCode');
        });

        it('generates rules with RequiredWithout for phone', function () {
            $rules = RegistrationDTO::rules();

            expect($rules)->toHaveKey('phone');
            expect($rules['phone'])->toContain('required_without:email');
        });
    });

    // -----------------------------------------------------------------------
    // Section 5: TaskListDTO with Collection attribute
    // -----------------------------------------------------------------------
    describe('TaskListDTO — Collection attribute integration', function () {
        it('creates with tasks collection', function () {
            $dto = TaskListDTO::fromArray([
                'projectName' => 'Website Redesign',
                'owner' => 'Alice',
                'tasks' => [
                    ['productName' => 'Design Homepage', 'price' => 0.0, 'quantity' => 1],
                    ['productName' => 'Setup CI/CD', 'price' => 50.0, 'quantity' => 1],
                ],
            ], validate: false);

            expect($dto->projectName)->toBe('Website Redesign');
            expect($dto->tasks)->toBeInstanceOf(DtoCollection::class);
            expect($dto->tasks->count())->toBe(2);
        });

        it('collection items are proper DTOs', function () {
            $dto = TaskListDTO::fromArray([
                'projectName' => 'API',
                'owner' => 'Bob',
                'tasks' => [
                    ['productName' => 'Endpoint A', 'price' => 10.0],
                ],
            ], validate: false);

            $first = $dto->tasks->first();
            expect($first)->toBeInstanceOf(OrderItemDTO::class);
            expect($first->productName)->toBe('Endpoint A');
            expect($first->price)->toBe(10.0);
            expect($first->quantity)->toBe(1); // default
        });

        it('collection pluck works', function () {
            $dto = TaskListDTO::fromArray([
                'projectName' => 'API',
                'owner' => 'Bob',
                'tasks' => [
                    ['productName' => 'Task 1', 'price' => 10.0],
                    ['productName' => 'Task 2', 'price' => 20.0],
                ],
            ], validate: false);

            $names = $dto->tasks->pluck('productName');
            expect($names)->toBe(['Task 1', 'Task 2']);
        });

        it('empty collection defaults correctly', function () {
            $dto = TaskListDTO::fromArray([
                'projectName' => 'Empty Project',
                'owner' => 'Charlie',
            ], validate: false);

            expect($dto->tasks)->toBeInstanceOf(DtoCollection::class);
            expect($dto->tasks->count())->toBe(0);
            expect($dto->tasks->isEmpty())->toBeTrue();
        });

        it('collection serialization is recursive', function () {
            $dto = TaskListDTO::fromArray([
                'projectName' => 'Serialize Test',
                'owner' => 'Dave',
                'tasks' => [
                    ['productName' => 'Task X', 'price' => 99.99, 'quantity' => 3],
                ],
            ], validate: false);

            $arr = $dto->toArray();
            expect($arr['tasks'])->toBeArray();
            expect($arr['tasks'][0])->toBeArray();
            expect($arr['tasks'][0]['productName'])->toBe('Task X');
            expect($arr['tasks'][0]['price'])->toBe(99.99);
        });

        it('collection filter works', function () {
            $dto = TaskListDTO::fromArray([
                'projectName' => 'Filter Test',
                'owner' => 'Eve',
                'tasks' => [
                    ['productName' => 'Cheap', 'price' => 5.0],
                    ['productName' => 'Expensive', 'price' => 500.0],
                    ['productName' => 'Mid', 'price' => 50.0],
                ],
            ], validate: false);

            $expensive = $dto->tasks->filter(
                fn (OrderItemDTO $item) => $item->price > 100.0
            );

            expect($expensive->count())->toBe(1);
            expect($expensive->first()->productName)->toBe('Expensive');
        });

        it('collection map works', function () {
            $dto = TaskListDTO::fromArray([
                'projectName' => 'Map Test',
                'owner' => 'Frank',
                'tasks' => [
                    ['productName' => 'A', 'price' => 10.0],
                    ['productName' => 'B', 'price' => 20.0],
                ],
            ], validate: false);

            $names = $dto->tasks->map(
                fn (OrderItemDTO $item) => $item->productName
            );

            expect($names)->toBe(['A', 'B']);
        });

        it('collection count and last work', function () {
            $dto = TaskListDTO::fromArray([
                'projectName' => 'Count Test',
                'owner' => 'Grace',
                'tasks' => [
                    ['productName' => 'First', 'price' => 1.0],
                    ['productName' => 'Last', 'price' => 2.0],
                ],
            ], validate: false);

            expect($dto->tasks->count())->toBe(2);
            expect($dto->tasks->last()->productName)->toBe('Last');
        });

        it('defaults completed to false', function () {
            $dto = TaskListDTO::fromArray([
                'projectName' => 'Project',
                'owner' => 'Hank',
            ], validate: false);

            expect($dto->completed)->toBeFalse();
        });

        it('rules include required for projectName and owner', function () {
            $rules = TaskListDTO::rules();

            expect($rules)->toHaveKey('projectName');
            expect($rules['projectName'])->toContain('required');
            expect($rules)->toHaveKey('owner');
            expect($rules['owner'])->toContain('required');
        });
    });

    // -----------------------------------------------------------------------
    // Section 6: RequiredWith/RequiredWithAll/RequiredWithout/RequiredWithoutAll
    // -----------------------------------------------------------------------
    describe('RequiredWith* — flexible field acceptance', function () {
        test('RequiredWith accepts string or array', function () {
            $with1 = new RequiredWith('field');
            expect($with1->fields)->toBe(['field']);

            $with2 = new RequiredWith(['a', 'b']);
            expect($with2->fields)->toBe(['a', 'b']);
        });

        test('RequiredWithAll accepts string or array', function () {
            $wa1 = new RequiredWithAll('field');
            expect($wa1->fields)->toBe(['field']);

            $wa2 = new RequiredWithAll(['a', 'b']);
            expect($wa2->fields)->toBe(['a', 'b']);
        });

        test('RequiredWithout accepts string or array', function () {
            $wo1 = new RequiredWithout('field');
            expect($wo1->fields)->toBe(['field']);

            $wo2 = new RequiredWithout(['a', 'b']);
            expect($wo2->fields)->toBe(['a', 'b']);
        });

        test('RequiredWithoutAll accepts string or array with empty default', function () {
            $woa1 = new RequiredWithoutAll;
            expect($woa1->fields)->toBe([]);

            $woa2 = new RequiredWithoutAll('field');
            expect($woa2->fields)->toBe(['field']);

            $woa3 = new RequiredWithoutAll(['x', 'y']);
            expect($woa3->fields)->toBe(['x', 'y']);
        });
    });

    // -----------------------------------------------------------------------
    // Section 7: Cross-attribute property access (readonly promoted)
    // -----------------------------------------------------------------------
    describe('attribute readonly property access', function () {
        test('Between min/max are typed as int|float', function () {
            $b = new Between(1, 100);
            expect($b->min)->toBe(1);
            expect($b->max)->toBe(100);
            expect($b->message)->toBeNull();

            $bFloat = new Between(0.5, 99.9, 'Custom message');
            expect($bFloat->min)->toBe(0.5);
            expect($bFloat->max)->toBe(99.9);
            expect($bFloat->message)->toBe('Custom message');
        });

        test('StartsWith accepts string or array', function () {
            $s1 = new StartsWith('https://');
            expect($s1->prefix)->toBe('https://');

            $s2 = new StartsWith(['+90', '+1']);
            expect($s2->prefix)->toBe(['+90', '+1']);
        });

        test('EndsWith accepts string or array', function () {
            $e1 = new EndsWith('@example.com');
            expect($e1->suffix)->toBe('@example.com');

            $e2 = new EndsWith(['@a.com', '@b.com']);
            expect($e2->suffix)->toBe(['@a.com', '@b.com']);
        });

        test('In stores values array', function () {
            $i = new In(['a', 'b', 'c']);
            expect($i->values)->toBe(['a', 'b', 'c']);
        });

        test('Size stores value as int', function () {
            $s = new Size(5);
            expect($s->value)->toBe(5);
        });

        test('Date with and without format', function () {
            $d1 = new Date;
            expect($d1->format)->toBeNull();

            $d2 = new Date('Y-m-d');
            expect($d2->format)->toBe('Y-m-d');
        });

        test('RequiredIf stores field and value', function () {
            $ri = new RequiredIf('type', 'admin');
            expect($ri->field)->toBe('type');
            expect($ri->value)->toBe('admin');
        });

        test('RequiredUnless stores field and value', function () {
            $ru = new RequiredUnless('status', 'active');
            expect($ru->field)->toBe('status');
            expect($ru->value)->toBe('active');
        });

        test('Same stores field', function () {
            $s = new Same('other_field');
            expect($s->field)->toBe('other_field');
        });

        test('Different stores field', function () {
            $d = new Different('other_field');
            expect($d->field)->toBe('other_field');
        });

        test('ArrayRule stores min/max', function () {
            $a1 = new ArrayRule;
            expect($a1->min)->toBeNull();
            expect($a1->max)->toBeNull();

            $a2 = new ArrayRule(min: 1, max: 10);
            expect($a2->min)->toBe(1);
            expect($a2->max)->toBe(10);
        });
    });
});
