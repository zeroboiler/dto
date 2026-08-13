<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Tests\Fixtures;

use BackedEnum;
use ZeroBoiler\DTO\Attributes\Cast;
use ZeroBoiler\DTO\Attributes\DefaultValue;
use ZeroBoiler\DTO\Attributes\Email;
use ZeroBoiler\DTO\Attributes\Enum;
use ZeroBoiler\DTO\Attributes\Hidden;
use ZeroBoiler\DTO\Attributes\Max;
use ZeroBoiler\DTO\Attributes\Min;
use ZeroBoiler\DTO\Attributes\Nullable;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\Attributes\Url;
use ZeroBoiler\DTO\DataTransferObject;

/**
 * Int-backed enum fixture for DTO enum property testing.
 */
enum ArticleStatus: int
{
    case DRAFT = 0;
    case PUBLISHED = 1;
    case ARCHIVED = 2;
}

/**
 * String-backed enum fixture for DTO enum property testing.
 */
enum Currency: string
{
    case USD = 'USD';
    case EUR = 'EUR';
    case TRY = 'TRY';
}

/**
 * Comprehensive DTO fixture for V5 audit — exercises all attribute types.
 *
 * Properties cover: Required, Email, Max, Min, Cast, DefaultValue, Hidden,
 * Nullable, Url, Enum, MapFrom.
 */
final class ArticleDTO extends DataTransferObject
{
    public function __construct(
        #[Required, Email]
        public readonly string $authorEmail,

        #[Required, Min(1), Max(200)]
        public readonly string $title,

        #[Required, Min(10)]
        public readonly string $body,

        #[Enum(ArticleStatus::class)]
        public readonly ArticleStatus $status = ArticleStatus::DRAFT,

        #[Enum(Currency::class)]
        public readonly Currency $currency = Currency::USD,

        #[Cast('integer')]
        public readonly int $viewCount = 0,

        #[Cast('float')]
        public readonly float $rating = 0.0,

        #[Max(255)]
        #[Url]
        public readonly ?string $coverImageUrl = null,

        #[Hidden]
        public readonly ?string $internalNote = null,

        #[DefaultValue(true)]
        public readonly bool $commentsEnabled = true,

        #[Nullable]
        public readonly ?string $excerpt = null,
    ) {}
}
