<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Attributes;

use Attribute;
use ZeroBoiler\DTO\Contracts\ValidationAttribute;

/**
 * Require this field when any of the given fields are absent.
 *
 *   #[RequiredWithout(['email'])]
 *   public ?string $username;
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class RequiredWithout implements ValidationAttribute
{
    /** @var array<int, string> */
    public readonly array $fields;

    /**
     * @param  string|array<int, string>  $fields  Field names; required if ANY is absent.
     */
    public function __construct(
        string|array $fields,
        public readonly ?string $message = null,
    ) {
        $this->fields = is_array($fields) ? $fields : [$fields];
    }

    public function ruleKey(): string
    {
        return 'required_without';
    }
}
