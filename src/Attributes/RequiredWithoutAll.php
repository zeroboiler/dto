<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Attributes;

use Attribute;

/**
 * Require this field when all of the given fields are absent.
 *
 *   #[RequiredWithoutAll('email', 'phone')]
 *   public ?string $username;
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class RequiredWithoutAll
{
    /**
     * @var array<int, string>
     */
    public array $fields;

    /**
     * @param  string|array<int, string>  $fields
     */
    public function __construct(
        string|array $fields,
        public ?string $message = null,
    ) {
        $this->fields = is_array($fields) ? $fields : [$fields];
    }
}
