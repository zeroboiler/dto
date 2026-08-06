<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO;

use ZeroBoiler\DTO\Exceptions\DTOException;
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
     *
     * @throws \Illuminate\Validation\ValidationException If validation fails
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
     *
     * @throws \Illuminate\Validation\ValidationException If validation fails
     */
    public function make(string $dtoClass, array $data): DataTransferObject
    {
        return $dtoClass::fromArray($data);
    }

    /**
     * Create a DTO instance from a JSON string.
     *
     * @param  class-string<DataTransferObject>  $dtoClass
     * @param  string  $json  JSON string to decode
     *
     * @throws DTOException If the JSON cannot be decoded
     */
    public function makeFromJson(string $dtoClass, string $json): DataTransferObject
    {
        return $dtoClass::fromJson($json);
    }

    /**
     * Generate OpenAPI schema for a DTO class.
     *
     * @param  class-string<DataTransferObject>  $dtoClass
     * @return array<string, mixed>
     *
     * @throws \LogicException If the DTO contains nested DTO references
     * @throws \ReflectionException If the class does not exist
     */
    public function schema(string $dtoClass): array
    {
        return OpenApiSchemaGenerator::generate($dtoClass);
    }
}
