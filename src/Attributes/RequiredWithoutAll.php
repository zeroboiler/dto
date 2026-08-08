<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Attributes;

use Attribute;
use ZeroBoiler\DTO\Contracts\ValidationAttribute;

/**
 * Require this field when all of the given fields are absent.
 *
 *   #[RequiredWithoutAll(['email', 'phone'])]
 *   public readonly ?string $username;
 *
 * Also accepts a single string:
 *   #[RequiredWithoutAll('email')]
 *   public readonly ?string $username;
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class RequiredWithoutAll implements ValidationAttribute
{
    /** @var array<int, string> */
    public readonly array $fields;

    /**
     * @param  string|array<int, string>  $fields  Field name(s); required if ALL are absent.
     * @param  string|null  $message  Custom validation message
     */
    public function __construct(
        string|array $fields = [],
        public readonly ?string $message = null,
    ) {
        $this->fields = is_array($fields) ? $fields : [$fields];
    }

    /** @return string The Laravel validation rule key ('required_without_all') */
    public function ruleKey(): string
    {
        return 'required_without_all';
    }
}
