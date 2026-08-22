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
 * Accepts a single field name or an array of field names:
 *
 *   #[RequiredWith('email')]
 *   #[RequiredWith(['email', 'phone'])]
 *   public readonly ?string $username;
 *
 * @see \ZeroBoiler\DTO\Contracts\ValidationAttribute For the validation attribute contract
 * @see \ZeroBoiler\DTO\Attributes\RequiredWithAll For requiring when ALL fields are present
 * @see \ZeroBoiler\DTO\Attributes\RequiredWithout For requiring when ANY field is absent
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class RequiredWith implements ValidationAttribute
{
    /** @var array<int, string> */
    public array $fields;

    /**
     * @param  string|array<int, string>  $fields  Field name(s); required if ANY is present.
     * @param  string|null  $message  Custom validation message
     */
    public function __construct(
        string|array $fields,
        public ?string $message = null,
    ) {
        $this->fields = is_array($fields) ? $fields : [$fields];
    }

    /**
     * @return string The Laravel validation rule key ('required_with')
     */
    public function ruleKey(): string
    {
        return 'required_with';
    }
}
