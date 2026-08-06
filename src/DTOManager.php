<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO;

use ZeroBoiler\DTO\Support\OpenApiSchemaGenerator;

/**
 * Runtime DTO helper — accessible via `DTO` facade or injected.
 *
 * Provides methods for validation, DTO creation, and OpenAPI schema
 * generation without direct static method usage.
 *
 *   DTO::validate(CreateUserDTO::class, $data);
 *   DTO::make(CreateUserDTO::class, $data);
 *   DTO::schema(CreateUserDTO::class);
 *
 * @see \ZeroBoiler\DTO\Facades\DTO
 */
final class DTOManager
{
    /**
     * Validate data against a DTO class.
     *
     * @param  class-string<DataTransferObject>  $dtoClass
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function validate(string $dtoClass, array $data): array
    {
        return $dtoClass::validateArray($data);
    }

    /**
     * Create a DTO instance from data.
     *
     * @param  class-string<DataTransferObject>  $dtoClass
     * @param  array<string, mixed>  $data
     */
    public function make(string $dtoClass, array $data): DataTransferObject
    {
        return $dtoClass::fromArray($data);
    }

    /**
     * Generate OpenAPI schema for a DTO class.
     *
     * @param  class-string<DataTransferObject>  $dtoClass
     * @return array<string, mixed>
     */
    public function schema(string $dtoClass): array
    {
        return OpenApiSchemaGenerator::generate($dtoClass);
    }
}
