<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Contracts;

use ZeroBoiler\DTO\DataTransferObject;

/**
 * Contract for DTOs that provide validation rules.
 *
 * All DTOs extending {@see DataTransferObject}
 * already implement {@see rules()} via attribute resolution.
 * This contract makes the guarantee explicit and discoverable,
 * and adds {@see rulesFor()} for action-scoped rule sets.
 *
 * @see DataTransferObject::rules() For attribute-based rule resolution
 * @see DataTransferObject::rulesFor() For action-scoped overrides
 * @see DataTransferObject::validateArray() For running validation
 * @see DataTransferObject::fromArray() For hydration with validation
 */
interface ValidatableDTO
{
    /**
     * Return validation rules for this DTO.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array;

    /**
     * Return validation rules scoped to a specific action.
     *
     * Common actions: 'create', 'update', 'patch'.
     * DTOs that don't override this method return the same
     * rules for all actions (i.e. {@see rules()}).
     *
     * @param  string  $action  The action context (e.g. 'create', 'update')
     * @return array<string, array<int, mixed>>
     */
    public static function rulesFor(string $action): array;
}
