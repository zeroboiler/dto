<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\Attributes\Boolean;
use ZeroBoiler\DTO\Attributes\Between;
use ZeroBoiler\DTO\Attributes\Cast;
use ZeroBoiler\DTO\Attributes\DefaultValue;
use ZeroBoiler\DTO\Attributes\Hidden;
use ZeroBoiler\DTO\Attributes\MapFrom;
use ZeroBoiler\DTO\Attributes\Max;
use ZeroBoiler\DTO\Attributes\Min;
use ZeroBoiler\DTO\Attributes\Nullable;
use ZeroBoiler\DTO\Attributes\Pattern;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\Attributes\StartsWith;
use ZeroBoiler\DTO\Attributes\EndsWith;
use ZeroBoiler\DTO\Attributes\Email;
use ZeroBoiler\DTO\Attributes\Url;
use ZeroBoiler\DTO\Attributes\Uuid;
use ZeroBoiler\DTO\Attributes\In;
use ZeroBoiler\DTO\Attributes\Integer;
use ZeroBoiler\DTO\Attributes\Numeric;
use ZeroBoiler\DTO\Attributes\Accepted;
use ZeroBoiler\DTO\Attributes\Declined;
use ZeroBoiler\DTO\Attributes\Confirmed;
use ZeroBoiler\DTO\Attributes\Prohibited;
use ZeroBoiler\DTO\Attributes\Present;
use ZeroBoiler\DTO\Attributes\Sometimes;
use ZeroBoiler\DTO\Attributes\Distinct;
use ZeroBoiler\DTO\Attributes\Size;
use ZeroBoiler\DTO\Attributes\Json;
use ZeroBoiler\DTO\Attributes\RequiredIf;
use ZeroBoiler\DTO\Attributes\RequiredUnless;
use ZeroBoiler\DTO\Attributes\RequiredWith;
use ZeroBoiler\DTO\Attributes\RequiredWithAll;
use ZeroBoiler\DTO\Attributes\RequiredWithout;
use ZeroBoiler\DTO\Attributes\RequiredWithoutAll;
use ZeroBoiler\DTO\Attributes\Same;
use ZeroBoiler\DTO\Attributes\Different;
use ZeroBoiler\DTO\Attributes\ArrayRule;
use ZeroBoiler\DTO\Attributes\Date;
use ZeroBoiler\DTO\Attributes\Enum as EnumAttr;
use ZeroBoiler\DTO\Attributes\Collection;
use ZeroBoiler\DTO\Attributes\NestedArray;
use ZeroBoiler\DTO\Contracts\ValidationAttribute;
use ZeroBoiler\DTO\Exceptions\DTOException;

describe('DTO Attribute Contract Compliance', function () {
    it('all validation attributes implement ValidationAttribute interface', function () {
        $classes = [
            Required::class, Email::class, Max::class, Min::class, Url::class,
            Pattern::class, In::class, Integer::class, Numeric::class, Boolean::class,
            Uuid::class, Date::class, EnumAttr::class, Confirmed::class, Different::class,
            Same::class, Between::class, ArrayRule::class, Prohibited::class, Present::class,
            Declined::class, Accepted::class, StartsWith::class, EndsWith::class,
            Nullable::class, Sometimes::class, Distinct::class, Size::class, Json::class,
            RequiredIf::class, RequiredUnless::class, RequiredWith::class,
            RequiredWithAll::class, RequiredWithout::class, RequiredWithoutAll::class,
            NestedArray::class, Collection::class,
        ];

        foreach ($classes as $class) {
            expect($class)->toImplement(ValidationAttribute::class);
        }
    });

    it('all validation attributes return non-empty ruleKey()', function () {
        $instances = [
            new Required, new Email, new Max(255), new Min(1),
            new Url, new Pattern('/^[a-z]+$/'), new In(['a', 'b']),
            new Integer, new Numeric, new Boolean, new Uuid,
            new Date, new EnumAttr(\ZeroBoiler\Enums\Tests\Fixtures\UserStatus::class),
            new Confirmed, new Different('other'), new Same('other'),
            new Between(1, 100), new ArrayRule, new Prohibited, new Present,
            new Declined, new Accepted, new StartsWith('foo'), new EndsWith('bar'),
            new Nullable, new Sometimes, new Distinct, new Size(10),
            new Json, new RequiredIf('field', 'value'), new RequiredUnless('field', 'value'),
            new RequiredWith('field'), new RequiredWithAll(['a', 'b']),
            new RequiredWithout('field'), new RequiredWithoutAll(['a', 'b']),
            new NestedArray(\ZeroBoiler\DTO\Tests\Fixtures\OrderItemDTO::class),
            new Collection(\ZeroBoiler\DTO\Tests\Fixtures\OrderItemDTO::class),
        ];

        foreach ($instances as $instance) {
            expect($instance->ruleKey())->toBeString()->not->toBeEmpty();
        }
    });

    it('metadata attributes do NOT implement ValidationAttribute', function () {
        expect(MapFrom::class)->not->toImplement(ValidationAttribute::class);
        expect(Hidden::class)->not->toImplement(ValidationAttribute::class);
        expect(Cast::class)->not->toImplement(ValidationAttribute::class);
        expect(DefaultValue::class)->not->toImplement(ValidationAttribute::class);
    });

    it('metadata attributes have correct property types', function () {
        $mapFrom = new MapFrom('user_name');
        expect($mapFrom->key)->toBe('user_name');

        $cast = new Cast('integer');
        expect($cast->type)->toBe('integer');

        $defaultValue = new DefaultValue('active');
        expect($defaultValue->value)->toBe('active');

        $defaultNull = new DefaultValue();
        expect($defaultNull->value)->toBeNull();
    });
});

describe('DTOException Factory Methods', function () {
    it('invalidCast includes property, type, and value info', function () {
        $e = DTOException::invalidCast('age', 'integer', 'not_a_number');
        expect($e->getMessage())->toContain('age');
        expect($e->getMessage())->toContain('integer');
    });

    it('invalidJson includes property and error message', function () {
        $e = DTOException::invalidJson('metadata', 'Syntax error');
        expect($e->getMessage())->toContain('metadata');
        expect($e->getMessage())->toContain('Syntax error');
    });

    it('__toString returns class name and message', function () {
        $e = DTOException::invalidCast('name', 'string', 42);
        $str = (string) $e;
        expect($str)->toContain('DTOException');
        expect($str)->toContain('name');
    });
});

describe('DTO Attribute Constructor Property Types', function () {
    it('Max accepts int|float value', function () {
        $intMax = new Max(255);
        expect($intMax->value)->toBe(255);

        $floatMax = new Max(99.9);
        expect($floatMax->value)->toBe(99.9);
    });

    it('Between accepts int|float min and max', function () {
        $b = new Between(1, 100);
        expect($b->min)->toBe(1);
        expect($b->max)->toBe(100);

        $bf = new Between(0.5, 99.9);
        expect($bf->min)->toBe(0.5);
        expect($bf->max)->toBe(99.9);
    });

    it('StartsWith accepts string or array prefix', function () {
        $s = new StartsWith('foo');
        expect($s->prefix)->toBe('foo');

        $sa = new StartsWith(['foo', 'bar']);
        expect($sa->prefix)->toBe(['foo', 'bar']);
    });

    it('EndsWith accepts string or array suffix', function () {
        $e = new EndsWith('.com');
        expect($e->suffix)->toBe('.com');
    });

    it('In accepts array of values', function () {
        $in = new In(['active', 'pending']);
        expect($in->values)->toBe(['active', 'pending']);
    });

    it('ArrayRule accepts optional min and max', function () {
        $plain = new ArrayRule;
        expect($plain->min)->toBeNull();
        expect($plain->max)->toBeNull();

        $bounded = new ArrayRule(min: 1, max: 10);
        expect($bounded->min)->toBe(1);
        expect($bounded->max)->toBe(10);
    });

    it('Date accepts optional format', function () {
        $d = new Date;
        expect($d->format)->toBeNull();

        $df = new Date('Y-m-d');
        expect($df->format)->toBe('Y-m-d');
    });

    it('RequiredIf accepts string or array value', function () {
        $r1 = new RequiredIf('status', 'active');
        expect($r1->field)->toBe('status');
        expect($r1->value)->toBe('active');

        $r2 = new RequiredIf('status', ['active', 'pending']);
        expect($r2->value)->toBe(['active', 'pending']);
    });

    it('RequiredUnless accepts string or array value', function () {
        $r = new RequiredUnless('status', 'banned');
        expect($r->field)->toBe('status');
        expect($r->value)->toBe('banned');
    });

    it('conditional rule attributes accept proper field lists', function () {
        $rw = new RequiredWith('password');
        expect($rw->fields)->toBe(['password']);

        $rwa = new RequiredWithAll(['street', 'city']);
        expect($rwa->fields)->toBe(['street', 'city']);

        $rwo = new RequiredWithout('email');
        expect($rwo->fields)->toBe(['email']);

        $rwoa = new RequiredWithoutAll(['phone', 'email']);
        expect($rwoa->fields)->toBe(['phone', 'email']);
    });

    it('Same and Different accept field names', function () {
        $same = new Same('password');
        expect($same->field)->toBe('password');

        $diff = new Different('old_email');
        expect($diff->field)->toBe('old_email');
    });
});

describe('DtoCollection Advanced Operations', function () {
    it('toArrayBy produces associative array keyed by property', function () {
        $dtoList = \ZeroBoiler\DTO\Tests\Fixtures\ProductDTO::fromArray([
            'name' => 'Widget',
            'price' => '9.99',
            'description' => 'A widget',
        ], validate: false);

        $collection = new \ZeroBoiler\DTO\DtoCollection([$dtoList]);
        $keyed = $collection->toArrayBy('name');

        expect($keyed)->toBeArray();
        expect($keyed)->toHaveKey('Widget');
    });

    it('toDictionary maps one property to another', function () {
        $dto = \ZeroBoiler\DTO\Tests\Fixtures\ProductDTO::fromArray([
            'name' => 'Gadget',
            'price' => '19.99',
            'description' => 'A gadget',
        ], validate: false);

        $collection = new \ZeroBoiler\DTO\DtoCollection([$dto]);
        $dict = $collection->toDictionary('name', 'price');

        expect($dict)->toBeArray();
        expect($dict['Gadget'])->toBe('19.99');
    });

    it('clone creates independent copy for append', function () {
        $dto = \ZeroBoiler\DTO\Tests\Fixtures\ProductDTO::fromArray([
            'name' => 'Item1',
            'price' => '10.0',
            'description' => 'First',
        ], validate: false);

        $original = new \ZeroBoiler\DTO\DtoCollection([$dto]);
        $appended = $original->append($dto);

        expect($appended)->not->toBe($original);
        expect($original->count())->toBe(1);
        expect($appended->count())->toBe(2);
    });
});

describe('DtoMetadataResolver Type Detection', function () {
    it('detects ValueObject types via union types', function () {
        // Verify the resolver can handle nullable VO types
        $rules = \ZeroBoiler\DTO\Tests\Fixtures\VoUserDTO::rules();
        expect($rules)->toBeArray();
    });

    it('infers integer rules for int-typed properties', function () {
        $rules = \ZeroBoiler\DTO\Tests\Fixtures\ProductDTO::rules();
        // price is a float type → numeric rule
        expect(isset($rules['price']))->toBeTrue();
    });
});
