<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Tests\Fixtures;

use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Support\Facades\Validator;
use ZeroBoiler\DTO\Attributes\DefaultValue;
use ZeroBoiler\DTO\Attributes\Email;
use ZeroBoiler\DTO\Attributes\Max;
use ZeroBoiler\DTO\Attributes\Min;
use ZeroBoiler\DTO\Attributes\Nullable;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\Attributes\Sometimes;
use ZeroBoiler\DTO\Contracts\ValidatableDTO;
use ZeroBoiler\DTO\DataTransferObject;

/**
 * DTO with action-scoped validation rules via rulesFor() override.
 *
 * Demonstrates how to provide different rules for 'create' vs 'update' actions.
 */
final class ActionScopedDTO extends DataTransferObject implements ValidatableDTO
{
    public function __construct(
        #[Required, Email]
        public readonly string $email,

        #[Required, Min(8)]
        public readonly string $password,

        #[Nullable]
        public readonly ?string $name = null,

        #[DefaultValue('user')]
        public readonly string $role = 'user',
    ) {}

    /**
     * Override rulesFor to provide action-specific validation.
     *
     * - 'create': email and password are required (default behavior)
     * - 'update': email and password become optional (sometimes)
     * - 'delete': no additional rules beyond base
     *
     * @return array<string, array<int, mixed>>
     */
    public static function rulesFor(string $action): array
    {
        $base = static::rules();

        return match ($action) {
            'create' => $base,
            'update' => [
                'email' => ['sometimes', 'email', 'max:255'],
                'password' => ['sometimes', 'min:8'],
                'name' => $base['name'] ?? ['nullable', 'string'],
                'role' => $base['role'] ?? ['string'],
            ],
            default => $base,
        };
    }
}
