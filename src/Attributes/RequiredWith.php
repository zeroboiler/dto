<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Attributes;

use Attribute;
use ZeroBoiler\DTO\Contracts\ValidationAttribute;

/**
 * Require this field when any of the given fields are present.
 *
 *   #[RequiredWith(['email', 'phone'])]
 *   public ?string $username;
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class RequiredWith implements ValidationAttribute
{
    /** @var array<int, string> */
    public readonly array $fields;

    /**
     * @param  string|array<int, string>  $fields  Field name(s); required if ANY is present.
     */
    public function __construct(
        string|array $fields,
        public readonly ?string $message = null,
    ) {
        $this->fields = is_array($fields) ? $fields : [$fields];
    }

    /** @return string The Laravel validation rule key ('required_with') */
    public function ruleKey(): string
    {
        return 'required_with';
    }
}
