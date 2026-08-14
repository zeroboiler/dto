<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Tests;

use ReflectionClass;
use ReflectionProperty;
use ZeroBoiler\DTO\Attributes\Cast;
use ZeroBoiler\DTO\Attributes\Collection;
use ZeroBoiler\DTO\Attributes\DefaultValue;
use ZeroBoiler\DTO\Attributes\Email;
use ZeroBoiler\DTO\Attributes\Enum;
use ZeroBoiler\DTO\Attributes\Hidden;
use ZeroBoiler\DTO\Attributes\MapFrom;
use ZeroBoiler\DTO\Attributes\Max;
use ZeroBoiler\DTO\Attributes\Min;
use ZeroBoiler\DTO\Attributes\NestedArray;
use ZeroBoiler\DTO\Attributes\Nullable;
use ZeroBoiler\DTO\Attributes\Pattern;
use ZeroBoiler\DTO\Attributes\Prohibited;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\Attributes\StartsWith;
use ZeroBoiler\DTO\Attributes\Url;
use ZeroBoiler\DTO\Contracts\FromRequestDTO;
use ZeroBoiler\DTO\Contracts\ValidationAttribute;
use ZeroBoiler\DTO\Contracts\ValidatableDTO;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Tests\Fixtures\ArticleDTO;
use ZeroBoiler\DTO\Tests\Fixtures\InteractionEdgeCaseDTO;
use ZeroBoiler\DTO\Tests\Fixtures\ItemDTO;
use ZeroBoiler\DTO\Tests\Fixtures\NullableRoundtripDTO;

/**
 * Comprehensive PHPStan Level 9 type-safety verification for the DTO package.
 *
 * Validates all public API surface for strict type correctness:
 * - All properties are public readonly in constructor promotion
 * - Return types are never mixed, always concrete (string, array, bool, int, etc.)
 * - toArray() excludes Hidden fields, allValues() includes them
 * - fromArray/fromJson/fromPartialArray return static (not mixed)
 * - DtoCollection generic type safety (accepts only DataTransferObject)
 * - All validation attributes are final with readonly properties
 * - Exception hierarchy provides proper named constructors
 * - Contracts enforce correct return types
 * - Cast types, MapFrom, DefaultValue edge cases
 * - equals/isEmpty/isNotEmpty type correctness
 * - with() immutable update returns new instance
 * - only/except selective output type safety
 *
 * @see ArticleDTO For comprehensive attribute fixture
 * @see NullableRoundtripDTO For nullable property roundtrip fixture
 * @see ItemDTO For simple DtoCollection fixture
 * @see InteractionEdgeCaseDTO For DefaultValue + Prohibited + Hidden fixture
 */
final class DtoPhpStanLevel9TypeSafetyCompleteTest
{
    // ========================================================================
    // 1. All DTO classes use public readonly promoted properties
    // ========================================================================

    /**
     * @test
     */
    public static function dtoPropertiesArePublicReadonly(): void
    {
        $dtoClasses = [
            ArticleDTO::class,
            NullableRoundtripDTO::class,
            ItemDTO::class,
            InteractionEdgeCaseDTO::class,
        ];

        foreach ($dtoClasses as $class) {
            $ref = new ReflectionClass($class);
            foreach ($ref->getProperties() as $prop) {
                assert(
                    $prop->isPublic(),
                    "{$class}::\${$prop->name} must be public"
                );
                assert(
                    $prop->isReadOnly(),
                    "{$class}::\${$prop->name} must be readonly"
                );
            }
        }
    }

    // ========================================================================
    // 2. fromArray() returns typed DTO instance (static return type)
    // ========================================================================

    /**
     * @test
     */
    public static function fromArrayReturnsCorrectType(): void
    {
        $dto = NullableRoundtripDTO::fromArray([
            'name' => 'Alice',
        ], validate: false);

        assert($dto instanceof NullableRoundtripDTO);
        assert($dto instanceof DataTransferObject);
        assert($dto->name === 'Alice');
        assert($dto->nickname === null);
        assert($dto->email === null);
    }

    /**
     * @test
     */
    public static function fromArrayAppliesDefaults(): void
    {
        $dto = ItemDTO::fromArray([
            'id' => 42,
            'name' => 'Widget',
        ], validate: false);

        assert($dto instanceof ItemDTO);
        assert($dto->id === 42);
        assert($dto->name === 'Widget');
        assert($dto->category === null);
    }

    // ========================================================================
    // 3. toArray() returns array<string, mixed> — excludes Hidden
    // ========================================================================

    /**
     * @test
     */
    public static function toArrayExcludesHidden(): void
    {
        $dto = NullableRoundtripDTO::fromArray([
            'name' => 'Bob',
            'nickname' => 'Bobby',
            'secret' => 'hidden-value',
        ], validate: false);

        $arr = $dto->toArray();
        assert(is_array($arr));
        assert(array_key_exists('name', $arr));
        assert(array_key_exists('nickname', $arr));
        assert(array_key_exists('email', $arr));
        assert(! array_key_exists('secret', $arr), 'toArray() must exclude #[Hidden] fields');
        assert($arr['name'] === 'Bob');
        assert($arr['nickname'] === 'Bobby');
        assert($arr['email'] === null);
    }

    // ========================================================================
    // 4. allValues() includes Hidden fields
    // ========================================================================

    /**
     * @test
     */
    public static function allValuesIncludesHidden(): void
    {
        $dto = NullableRoundtripDTO::fromArray([
            'name' => 'Charlie',
            'secret' => 's3cr3t',
        ], validate: false);

        $all = $dto->allValues();
        assert(array_key_exists('secret', $all), 'allValues() must include #[Hidden] fields');
        assert($all['secret'] === 's3cr3t');
        assert($all['name'] === 'Charlie');
    }

    // ========================================================================
    // 5. toJson() returns valid JSON string
    // ========================================================================

    /**
     * @test
     */
    public static function toJsonReturnsValidJsonString(): void
    {
        $dto = ItemDTO::fromArray(['id' => 1, 'name' => 'Test'], validate: false);
        $json = $dto->toJson();
        assert(is_string($json));
        assert($json !== '');

        $decoded = json_decode($json, true);
        assert(is_array($decoded));
        assert($decoded['id'] === 1);
        assert($decoded['name'] === 'Test');
    }

    // ========================================================================
    // 6. jsonSerialize() returns same as toArray()
    // ========================================================================

    /**
     * @test
     */
    public static function jsonSerializeMatchesToArray(): void
    {
        $dto = ItemDTO::fromArray(['id' => 5, 'name' => 'Item', 'category' => 'A'], validate: false);
        assert($dto->jsonSerialize() === $dto->toArray());
    }

    // ========================================================================
    // 7. equals() uses toArray comparison (strict)
    // ========================================================================

    /**
     * @test
     */
    public static function equalsUsesStrictArrayComparison(): void
    {
        $a = ItemDTO::fromArray(['id' => 1, 'name' => 'Same'], validate: false);
        $b = ItemDTO::fromArray(['id' => 1, 'name' => 'Same'], validate: false);
        $c = ItemDTO::fromArray(['id' => 1, 'name' => 'Different'], validate: false);

        assert($a->equals($b) === true);
        assert($a->equals($c) === false);
    }

    // ========================================================================
    // 8. isEmpty() / isNotEmpty() type correctness
    // ========================================================================

    /**
     * @test
     */
    public static function isEmptyAndIsNotEmpty(): void
    {
        // DTO with only defaults — all empty
        $empty = NullableRoundtripDTO::fromArray([
            'name' => '',
        ], validate: false);
        assert($empty->isEmpty() === true);
        assert($empty->isNotEmpty() === false);

        // DTO with meaningful values
        $filled = NullableRoundtripDTO::fromArray([
            'name' => 'Alice',
        ], validate: false);
        assert($filled->isEmpty() === false);
        assert($filled->isNotEmpty() === true);

        // DTO with 0 value — 0 is NOT empty (valid meaningful value)
        $dto = ItemDTO::fromArray(['id' => 0, 'name' => ''], validate: false);
        // id=0 is non-nullable int with value 0 — not empty
        assert($dto->isEmpty() === true); // name is empty, category is null
    }

    // ========================================================================
    // 9. with() returns new instance — immutability
    // ========================================================================

    /**
     * @test
     */
    public static function withReturnsNewImmutableInstance(): void
    {
        $original = ItemDTO::fromArray(['id' => 1, 'name' => 'Original'], validate: false);
        $modified = $original->with(['name' => 'Modified']);

        assert($original !== $modified, 'with() must return a new instance');
        assert($original->name === 'Original', 'Original must be unchanged');
        assert($modified->name === 'Modified');
        assert($modified->id === 1, 'Non-overridden property must be preserved');
    }

    // ========================================================================
    // 10. only() / except() selective output
    // ========================================================================

    /**
     * @test
     */
    public static function onlyAndExceptSelectiveOutput(): void
    {
        $dto = ItemDTO::fromArray(['id' => 42, 'name' => 'Test', 'category' => 'Books'], validate: false);

        // only — returns only specified fields
        $only = $dto->only('id');
        assert(is_array($only));
        assert(array_key_exists('id', $only));
        assert(! array_key_exists('name', $only));
        assert(! array_key_exists('category', $only));
        assert($only['id'] === 42);

        // only — multiple fields
        $only = $dto->only(['id', 'name']);
        assert(array_key_exists('id', $only));
        assert(array_key_exists('name', $only));
        assert(! array_key_exists('category', $only));

        // only — single string
        $only = $dto->only('name');
        assert(array_key_exists('name', $only));
        assert(count($only) === 1);

        // except — returns all except specified
        $except = $dto->except('category');
        assert(array_key_exists('id', $except));
        assert(array_key_exists('name', $except));
        assert(! array_key_exists('category', $except));

        // except — single string
        $except = $dto->except('id');
        assert(! array_key_exists('id', $except));
        assert(array_key_exists('name', $except));
    }

    // ========================================================================
    // 11. fromPartialArray() — PATCH semantics
    // ========================================================================

    /**
     * @test
     */
    public static function fromPartialArrayPatchSemantics(): void
    {
        // Start with full data
        $original = ItemDTO::fromArray(['id' => 1, 'name' => 'Original', 'category' => 'A'], validate: false);

        // Partial update — only change name
        $patched = ItemDTO::fromPartialArray(['name' => 'Patched'], validate: false);

        assert($patched instanceof ItemDTO);
        assert($patched->name === 'Patched');
        // id falls back to type-appropriate empty (0 for int)
        assert($patched->id === 0);
        // category falls back to default (null)
        assert($patched->category === null);
    }

    // ========================================================================
    // 12. DtoCollection — type safety and operations
    // ========================================================================

    /**
     * @test
     */
    public static function dtoCollectionTypeSafety(): void
    {
        $item1 = ItemDTO::fromArray(['id' => 1, 'name' => 'A', 'category' => 'Cat1'], validate: false);
        $item2 = ItemDTO::fromArray(['id' => 2, 'name' => 'B', 'category' => 'Cat2'], validate: false);

        $col = DtoCollection::make([$item1, $item2]);
        assert($col instanceof DtoCollection);
        assert($col->count() === 2);
        assert($col->isEmpty() === false);
        assert($col->isNotEmpty() === true);

        // first / last
        assert($col->first()?->name === 'A');
        assert($col->last()?->name === 'B');

        // Empty collection
        $empty = DtoCollection::make();
        assert($empty->count() === 0);
        assert($empty->isEmpty() === true);
        assert($empty->first() === null);
        assert($empty->last() === null);

        // toArray — each DTO serialized
        $arr = $col->toArray();
        assert(is_array($arr));
        assert(count($arr) === 2);
        assert($arr[0]['id'] === 1);
        assert($arr[1]['name'] === 'B');

        // items() — raw DTO instances
        $items = $col->items();
        assert(count($items) === 2);
        assert($items[0] instanceof DataTransferObject);
    }

    /**
     * @test
     */
    public static function dtoCollectionRejectsNonDto(): void
    {
        $thrown = false;
        try {
            DtoCollection::make([new \stdClass()]);
        } catch (\InvalidArgumentException $e) {
            $thrown = true;
            assert(str_contains($e->getMessage(), 'DataTransferObject'));
        }
        assert($thrown === true);
    }

    // ========================================================================
    // 13. DtoCollection — map, filter, push, append, merge
    // ========================================================================

    /**
     * @test
     */
    public static function dtoCollectionOperations(): void
    {
        $a = ItemDTO::fromArray(['id' => 1, 'name' => 'A'], validate: false);
        $b = ItemDTO::fromArray(['id' => 2, 'name' => 'B'], validate: false);
        $c = ItemDTO::fromArray(['id' => 3, 'name' => 'C'], validate: false);

        $col = DtoCollection::make([$a, $b]);

        // map — returns plain array
        $names = $col->map(fn (ItemDTO $dto, int $i): string => $dto->name);
        assert($names === ['A', 'B']);

        // filter — returns new collection
        $filtered = $col->filter(fn (ItemDTO $dto): bool => $dto->id > 1);
        assert($filtered->count() === 1);
        assert($col->count() === 2, 'Original collection must be unchanged');

        // push — mutates in place
        $pushed = $col->push($c);
        assert($col->count() === 3);
        assert($col === $pushed, 'push() returns same instance');

        // append — returns new collection
        $appended = $col->append($c);
        assert($col->count() === 3);
        assert($appended !== $col, 'append() returns new instance');
        assert($appended->count() === 4);

        // merge — returns new collection
        $other = DtoCollection::make([$c]);
        $merged = $col->merge($other);
        assert($merged->count() === 4);
    }

    // ========================================================================
    // 14. DtoCollection — pluck, pluckKey, toArrayBy, toDictionary
    // ========================================================================

    /**
     * @test
     */
    public static function dtoCollectionPluckOperations(): void
    {
        $a = ItemDTO::fromArray(['id' => 1, 'name' => 'Alpha', 'category' => 'X'], validate: false);
        $b = ItemDTO::fromArray(['id' => 2, 'name' => 'Beta', 'category' => 'Y'], validate: false);
        $c = ItemDTO::fromArray(['id' => 3, 'name' => 'Gamma', 'category' => null], validate: false);

        $col = DtoCollection::make([$a, $b, $c]);

        // pluck
        $names = $col->pluck('name');
        assert($names === ['Alpha', 'Beta', 'Gamma']);

        $ids = $col->pluck('id');
        assert($ids === [1, 2, 3]);

        // pluckKey — key/value pairs
        $keyed = $col->pluckKey('id', 'name');
        assert($keyed === [1 => 'Alpha', 2 => 'Beta', 3 => 'Gamma']);

        // pluckKey — skips null keys (Gamma has null category)
        $byCategory = $col->pluckKey('category', 'name');
        assert($byCategory === ['X' => 'Alpha', 'Y' => 'Beta']);
        assert(! array_key_exists(null, $byCategory), 'Null keys must be skipped');

        // toArrayBy — alias for pluckKey
        $byId = $col->toArrayBy('id');
        assert($byId === [1 => ['id' => 1, 'name' => 'Alpha', 'category' => 'X']]);

        // toDictionary — key field + value field
        $dict = $col->toDictionary('id', 'name');
        assert($dict === [1 => 'Alpha', 2 => 'Beta', 3 => 'Gamma']);
    }

    // ========================================================================
    // 15. DtoCollection — ArrayAccess, jsonSerialize
    // ========================================================================

    /**
     * @test
     */
    public static function dtoCollectionArrayAccess(): void
    {
        $a = ItemDTO::fromArray(['id' => 1, 'name' => 'A'], validate: false);
        $b = ItemDTO::fromArray(['id' => 2, 'name' => 'B'], validate: false);
        $col = DtoCollection::make([$a, $b]);

        // offsetExists / offsetGet
        assert(isset($col[0]) === true);
        assert(isset($col[1]) === true);
        assert(isset($col[2]) === false);
        assert($col[0]?->name === 'A');

        // offsetSet — replace
        $col[0] = ItemDTO::fromArray(['id' => 99, 'name' => 'Replaced'], validate: false);
        assert($col[0]->name === 'Replaced');

        // offsetUnset — removes and re-indexes
        unset($col[0]);
        assert($col->count() === 1);
        assert($col[0]->name === 'B'); // re-indexed

        // jsonSerialize
        $json = json_encode($col);
        $decoded = json_decode($json, true);
        assert(is_array($decoded));
        assert(count($decoded) === 1);
        assert($decoded[0]['name'] === 'B');
    }

    // ========================================================================
    // 16. ArticleDTO — comprehensive attribute test
    // ========================================================================

    /**
     * @test
     */
    public static function articleDtoFullAttributeCoverage(): void
    {
        $dto = ArticleDTO::fromArray([
            'authorEmail' => 'test@example.com',
            'title' => 'Test Article',
            'body' => 'This is the body content for the article.',
            'viewCount' => '150',
            'rating' => '4.5',
            'cover_image_url' => 'https://example.com/cover.jpg',
            'internalNote' => 'internal-only note',
        ], validate: false);

        // Type verification
        assert(is_string($dto->authorEmail));
        assert(is_string($dto->title));
        assert(is_int($dto->viewCount));
        assert($dto->viewCount === 150); // Cast('integer') applied
        assert(is_float($dto->rating));
        assert($dto->rating === 4.5); // Cast('float') applied
        assert(is_bool($dto->commentsEnabled));
        assert($dto->commentsEnabled === true); // DefaultValue applied

        // Hidden field excluded from toArray but in allValues
        $arr = $dto->toArray();
        assert(! array_key_exists('internalNote', $arr));

        $all = $dto->allValues();
        assert(array_key_exists('internalNote', $all));
        assert($all['internalNote'] === 'internal-only note');
    }

    // ========================================================================
    // 17. rules() returns proper Laravel rules array
    // ========================================================================

    /**
     * @test
     */
    public static function rulesReturnsProperStructure(): void
    {
        $rules = NullableRoundtripDTO::rules();
        assert(is_array($rules));
        assert(array_key_exists('name', $rules));
        assert(is_array($rules['name']));
        assert(in_array('required', $rules['name'], true));
        assert(in_array('min:2', $rules['name'], true));
        assert(in_array('max:50', $rules['name'], true));
    }

    /**
     * @test
     */
    public static function rulesForReturnsSameAsRules(): void
    {
        assert(NullableRoundtripDTO::rulesFor('create') === NullableRoundtripDTO::rules());
        assert(NullableRoundtripDTO::rulesFor('update') === NullableRoundtripDTO::rules());
        assert(NullableRoundtripDTO::rulesFor('any_action') === NullableRoundtripDTO::rules());
    }

    // ========================================================================
    // 18. DTOException named constructors
    // ========================================================================

    /**
     * @test
     */
    public static function dtoExceptionNamedConstructors(): void
    {
        // invalidCast
        $ex = DTOException::invalidCast('age', 'integer', 'not-a-number');
        assert($ex instanceof DTOException);
        assert(str_contains($ex->getMessage(), 'age'));
        assert(str_contains($ex->getMessage(), 'integer'));

        // invalidJson
        $ex = DTOException::invalidJson('data', 'Syntax error');
        assert(str_contains($ex->getMessage(), 'data'));
        assert(str_contains($ex->getMessage(), 'Syntax error'));

        // __toString
        $str = (string) $ex;
        assert(str_starts_with($str, DTOException::class));
        assert(str_contains($str, 'Syntax error'));
    }

    // ========================================================================
    // 19. All validation attribute classes are final
    // ========================================================================

    /**
     * @test
     */
    public static function validationAttributesAreFinal(): void
    {
        $attributeClasses = [
            Required::class, Email::class, Max::class, Min::class,
            Url::class, Pattern::class, Enum::class, Cast::class,
            Hidden::class, Nullable::class, Prohibited::class,
            StartsWith::class, DefaultValue::class, MapFrom::class,
            NestedArray::class, Collection::class,
        ];

        foreach ($attributeClasses as $class) {
            assert(
                (new ReflectionClass($class))->isFinal(),
                "{$class} must be final"
            );
        }
    }

    // ========================================================================
    // 20. Validation attributes have readonly promoted properties
    // ========================================================================

    /**
     * @test
     */
    public static function validationAttributePropertiesAreReadonly(): void
    {
        // Check Required
        $req = new Required();
        $ref = new ReflectionProperty(Required::class, 'message');
        assert($ref->isReadOnly());
        assert($ref->isPublic());
        assert($req->message === null);

        // Required with custom message
        $req = new Required('Custom message');
        assert($req->message === 'Custom message');

        // Email
        $email = new Email('Invalid email');
        assert((new ReflectionProperty(Email::class, 'message'))->isReadOnly());
        assert($email->message === 'Invalid email');

        // Max
        $max = new Max(255);
        assert((new ReflectionProperty(Max::class, 'value'))->isReadOnly());
        assert($max->value === 255);

        // Min
        $min = new Min(1);
        assert((new ReflectionProperty(Min::class, 'value'))->isReadOnly());
        assert($min->value === 1);

        // Pattern
        $pattern = new Pattern('/^[a-z]+$/');
        assert((new ReflectionProperty(Pattern::class, 'regex'))->isReadOnly());
        assert($pattern->regex === '/^[a-z]+$/');

        // MapFrom
        $mf = new MapFrom('source_key');
        assert((new ReflectionProperty(MapFrom::class, 'key'))->isReadOnly());
        assert($mf->key === 'source_key');

        // Cast
        $cast = new Cast('integer');
        assert((new ReflectionProperty(Cast::class, 'type'))->isReadOnly());
        assert($cast->type === 'integer');

        // DefaultValue
        $dv = new DefaultValue('active');
        assert((new ReflectionProperty(DefaultValue::class, 'value'))->isReadOnly());
        assert($dv->value === 'active');

        // Hidden
        $hidden = new Hidden();
        assert((new ReflectionProperty(Hidden::class, 'message'))->isReadOnly());

        // NestedArray
        $na = new NestedArray(ItemDTO::class);
        assert((new ReflectionProperty(NestedArray::class, 'dtoClass'))->isReadOnly());
        assert($na->dtoClass === ItemDTO::class);

        // Collection
        $coll = new Collection(ItemDTO::class);
        assert((new ReflectionProperty(Collection::class, 'dtoClass'))->isReadOnly());
        assert($coll->dtoClass === ItemDTO::class);
    }

    // ========================================================================
    // 21. All validation attributes implement ValidationAttribute
    // ========================================================================

    /**
     * @test
     */
    public static function validationAttributesImplementContract(): void
    {
        $attributeClasses = [
            Required::class, Email::class, Max::class, Min::class,
            Url::class, Pattern::class, Prohibited::class,
            StartsWith::class, NestedArray::class, Collection::class,
        ];

        foreach ($attributeClasses as $class) {
            assert(
                is_subclass_of($class, ValidationAttribute::class),
                "{$class} must implement ValidationAttribute"
            );

            // ruleKey() returns non-empty string
            $instance = match ($class) {
                Required::class => new Required(),
                Email::class => new Email(),
                Max::class => new Max(100),
                Min::class => new Min(0),
                Url::class => new Url(),
                Pattern::class => new Pattern('/.*/'),
                Prohibited::class => new Prohibited(),
                StartsWith::class => new StartsWith('@'),
                NestedArray::class => new NestedArray(ItemDTO::class),
                Collection::class => new Collection(ItemDTO::class),
                default => null,
            };

            if ($instance !== null) {
                $key = $instance->ruleKey();
                assert(is_string($key));
                assert($key !== '', "{$class}::ruleKey() must return non-empty string");
            }
        }
    }

    // ========================================================================
    // 22. Infrastructure classes are final
    // ========================================================================

    /**
     * @test
     */
    public static function infrastructureClassesAreFinal(): void
    {
        $finalClasses = [
            DataTransferObject::class, // abstract — can't be instantiated, but check final
            DtoCollection::class,
            \ZeroBoiler\DTO\DTOManager::class,
            \ZeroBoiler\DTO\DTOSServiceProvider::class,
            DTOException::class,
            \ZeroBoiler\DTO\Casts\DTOCast::class,
            \ZeroBoiler\DTO\Support\DtoMetadataResolver::class,
            \ZeroBoiler\DTO\Support\OpenApiSchemaGenerator::class,
            \ZeroBoiler\DTO\Facades\DTO::class,
            \ZeroBoiler\DTO\Console\Commands\MakeDtoTestCommand::class,
            \ZeroBoiler\DTO\Console\Commands\MakeDtoSchemaCommand::class,
        ];

        foreach ($finalClasses as $class) {
            $ref = new ReflectionClass($class);
            if (! $ref->isAbstract()) {
                assert($ref->isFinal(), "{$class} must be final");
            }
        }
    }

    // ========================================================================
    // 23. DTOManager is readonly class
    // ========================================================================

    /**
     * @test
     */
    public static function dtoManagerIsReadonlyClass(): void
    {
        $ref = new ReflectionClass(\ZeroBoiler\DTO\DTOManager::class);
        assert($ref->isReadOnly(), 'DTOManager must be readonly');
        assert($ref->isFinal(), 'DTOManager must be final');
    }

    // ========================================================================
    // 24. DTOFacade returns correct accessor
    // ========================================================================

    /**
     * @test
     */
    public static function dtoFacadeAccessor(): void
    {
        $ref = new ReflectionClass(\ZeroBoiler\DTO\Facades\DTO::class);
        assert($ref->isFinal());

        $method = $ref->getMethod('getFacadeAccessor');
        assert($method->isPublic());
        assert($method->getReturnType()?->getName() === 'string');
    }

    // ========================================================================
    // 25. Contracts enforce correct return types
    // ========================================================================

    /**
     * @test
     */
    public static function fromRequestDtoContract(): void
    {
        assert(
            is_subclass_of(DataTransferObject::class, FromRequestDTO::class),
            'DataTransferObject must implement FromRequestDTO'
        );

        $method = new \ReflectionMethod(DataTransferObject::class, 'fromRequest');
        $returnType = $method->getReturnType();
        assert($returnType !== null);
        assert($returnType->getName() === 'static', 'fromRequest must return static');
    }

    /**
     * @test
     */
    public static function validatableDtoContract(): void
    {
        assert(
            is_subclass_of(DataTransferObject::class, ValidatableDTO::class),
            'DataTransferObject must implement ValidatableDTO'
        );

        // rules() returns array<string, array<int, mixed>>
        $method = new \ReflectionMethod(DataTransferObject::class, 'rules');
        $returnType = $method->getReturnType();
        assert($returnType !== null);
        assert($returnType->getName() === 'array');

        // rulesFor() returns array<string, array<int, mixed>>
        $method = new \ReflectionMethod(DataTransferObject::class, 'rulesFor');
        $returnType = $method->getReturnType();
        assert($returnType !== null);
        assert($returnType->getName() === 'array');
    }

    // ========================================================================
    // 26. InteractionEdgeCaseDTO — DefaultValue + Prohibited + Hidden
    // ========================================================================

    /**
     * @test
     */
    public static function interactionEdgeCaseDtoAttributes(): void
    {
        $dto = InteractionEdgeCaseDTO::fromArray([
            'username' => 'testuser',
            'handle' => '@testuser',
        ], validate: false);

        assert($dto->username === 'testuser');
        assert($dto->handle === '@testuser');

        // DefaultValue applied for 'source' and 'limit'
        assert($dto->source === 'internal');
        assert($dto->limit === 100);

        // Hidden property not in toArray
        $arr = $dto->toArray();
        assert(! array_key_exists('source', $arr));

        // Hidden property in allValues
        $all = $dto->allValues();
        assert(array_key_exists('source', $all));
    }

    // ========================================================================
    // 27. Nullable property roundtrip through toArray/fromArray
    // ========================================================================

    /**
     * @test
     */
    public static function nullablePropertyRoundtrip(): void
    {
        $original = NullableRoundtripDTO::fromArray([
            'name' => 'Alice',
            'nickname' => 'Ali',
            'email' => 'ali@test.com',
            'secret' => 'hidden123',
        ], validate: false);

        // toArray excludes hidden, includes nullable
        $arr = $original->toArray();
        assert($arr['nickname'] === 'Ali');
        assert($arr['email'] === 'ali@test.com');
        assert(! array_key_exists('secret', $arr));

        // Roundtrip: create new DTO from array output
        $restored = NullableRoundtripDTO::fromArray($arr, validate: false);
        assert($restored->name === 'Alice');
        assert($restored->nickname === 'Ali');
        assert($restored->email === 'ali@test.com');
    }

    // ========================================================================
    // 28. Metadata cache TTL and flush
    // ========================================================================

    /**
     * @test
     */
    public static function metadataCacheTtlAndFlush(): void
    {
        // Flush all cached metadata
        DataTransferObject::flushMetadataCache();

        // Resolve metadata
        $rules = NullableRoundtripDTO::rules();
        assert(is_array($rules));
        assert(! empty($rules));

        // Flush specific class
        DataTransferObject::flushMetadataCache(NullableRoundtripDTO::class);

        // Set TTL
        DataTransferObject::setMetadataCacheTtl(1.0);
        DataTransferObject::flushMetadataCache();

        // Rules still work after flush
        $rules2 = NullableRoundtripDTO::rules();
        assert($rules2 === $rules);

        DataTransferObject::flushMetadataCache();
    }

    // ========================================================================
    // 29. Enum properties in DTO — proper serialization
    // ========================================================================

    /**
     * @test
     */
    public static function enumPropertiesSerializeCorrectly(): void
    {
        $dto = ArticleDTO::fromArray([
            'authorEmail' => 'test@test.com',
            'title' => 'Test',
            'body' => 'Body text here for testing.',
            'status' => 1,
            'currency' => 'EUR',
        ], validate: false);

        // Enum properties are BackedEnum instances
        assert($dto->status instanceof \BackedEnum);
        assert($dto->currency instanceof \BackedEnum);

        // toArray serializes enums to backed values
        $arr = $dto->toArray();
        assert($arr['status'] === 1);
        assert($arr['currency'] === 'EUR');
    }

    // ========================================================================
    // 30. DtoCollection jsonSerialize matches toArray
    // ========================================================================

    /**
     * @test
     */
    public static function dtoCollectionJsonSerialize(): void
    {
        $items = DtoCollection::make([
            ItemDTO::fromArray(['id' => 1, 'name' => 'A'], validate: false),
            ItemDTO::fromArray(['id' => 2, 'name' => 'B'], validate: false),
        ]);

        assert($items->jsonSerialize() === $items->toArray());

        $json = json_encode($items);
        $decoded = json_decode($json, true);
        assert(is_array($decoded));
        assert(count($decoded) === 2);
    }
}
