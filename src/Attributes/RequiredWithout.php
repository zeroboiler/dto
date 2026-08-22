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
 * Accepts a single field name or an array of field names:
 *
 *   #[RequiredWithout('email')]
 *   #[RequiredWithout(['email', 'phone'])]
 *   public readonly ?string $username;
 *
 * @see \ZeroBoiler\DTO\Contracts\ValidationAttribute For the validation attribute contract
 * @see \ZeroBoiler\DTO\Attributes\RequiredWithoutAll For requiring when ALL fields are absent
 * @see \ZeroBoiler\DTO\Attributes\RequiredWith For requiring when ANY field is present
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class RequiredWithout implements ValidationAttribute
{
    /** @var array<int, string> */
    public array $fields;

    /**
     * @param  string|array<int, string>  $fields  Field names; required if ANY is absent.
     * @param  string|null  $message  Custom validation message
     */
    public function __construct(
        string|array $fields,
        public ?string $message = null,
    ) {
        $this->fields = is_array($fields) ? $fields : [$fields];
    }

    /**
     * @return string The Laravel validation rule key ('required_without')
     */
    public function ruleKey(): string
    {
        return 'required_without';
    }
}
