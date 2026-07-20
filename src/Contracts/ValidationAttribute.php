<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Contracts;

/**
 * Marker interface for attributes that participate in validation.
 *
 * Implementations MUST return the Laravel validation rule key
 * that corresponds to their generated rule. This key is used
 * for validation message generation, replacing the fragile
 * class-name parsing approach (#9).
 *
 * @see https://laravel.com/docs/validation#rule-error-messages
 */
interface ValidationAttribute
{
    /**
     * Return the Laravel validation rule key for message generation.
     *
     * Examples: 'email', 'max', 'starts_with', 'required_if'
     *
     * For attributes that generate multiple rules (e.g. ArrayRule
     * which may add both 'array' and 'min'/'max'), return the
     * primary rule key — the one most likely to need a custom message.
     */
    public function ruleKey(): string;
}
