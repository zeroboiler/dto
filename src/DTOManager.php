<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO;

use Illuminate\Http\Request;
use ZeroBoiler\DTO\Exceptions\DTOException;
use ZeroBoiler\DTO\Support\OpenApiSchemaGenerator;

/**
 * Runtime DTO helper — accessible via `DTO` facade or injected.
 *
 * Provides methods for validation, DTO creation, rules retrieval,
 * and OpenAPI schema generation without direct static method usage.
 *
 *   DTO::validate(CreateUserDTO::class, $data);
 *   DTO::make(CreateUserDTO::class, $data);
 *   DTO::makeFromJson(CreateUserDTO::class, $json);
 *   DTO::fromJson(CreateUserDTO::class, $json);
 *   DTO::fromPartialArray(CreateUserDTO::class, $data);
 *   DTO::fromPartialRequest(CreateUserDTO::class, $request);
 *   DTO::rules(CreateUserDTO::class);
 *   DTO::rulesFor(CreateUserDTO::class, 'update');
 *   DTO::schema(CreateUserDTO::class);
 *
 * @see \ZeroBoiler\DTO\Facades\DTO
 */
final readonly class DTOManager
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
     * Convenience alias for {@see fromJson()}. Both methods behave identically —
     * `makeFromJson()` is provided for naming consistency with {@see make()}.
     *
     * @param  class-string<DataTransferObject>  $dtoClass
     * @param  string  $json  JSON string to decode
     *
     * @throws DTOException If the JSON cannot be decoded
     */
    public function makeFromJson(string $dtoClass, string $json): DataTransferObject
    {
        return $this->fromJson($dtoClass, $json);
    }

    /**
     * Get validation rules for a DTO class.
     *
     * @param  class-string<DataTransferObject>  $dtoClass
     * @return array<string, array<int, mixed>>
     */
    public function rules(string $dtoClass): array
    {
        return $dtoClass::rules();
    }

    /**
     * Get action-scoped validation rules for a DTO class.
     *
     * @param  class-string<DataTransferObject>  $dtoClass
     * @param  string  $action  The action context (e.g. 'create', 'update', 'patch')
     * @return array<string, array<int, mixed>>
     */
    public function rulesFor(string $dtoClass, string $action): array
    {
        return $dtoClass::rulesFor($action);
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

    /**
     * Create a DTO instance from a partial array (PATCH semantics).
     *
     * Only fields present in $data are validated and hydrated.
     * Missing fields fall back to their default values or type-appropriate empty values.
     *
     * @param  class-string<DataTransferObject>  $dtoClass
     * @param  array<string, mixed>  $data
     *
     * @throws \Illuminate\Validation\ValidationException If validation fails
     */
    public function fromPartialArray(string $dtoClass, array $data): DataTransferObject
    {
        return $dtoClass::fromPartialArray($data);
    }

    /**
     * Create a DTO instance from a partial HTTP request (PATCH semantics).
     *
     * @param  class-string<DataTransferObject>  $dtoClass
     * @param  Request  $request
     *
     * @throws \Illuminate\Validation\ValidationException If validation fails
     */
    public function fromPartialRequest(string $dtoClass, Request $request): DataTransferObject
    {
        return $dtoClass::fromPartialRequest($request);
    }

    /**
     * Create a DTO instance from a JSON string.
     *
     * @param  class-string<DataTransferObject>  $dtoClass
     * @param  string  $json
     *
     * @throws DTOException If the JSON cannot be decoded
     * @throws \Illuminate\Validation\ValidationException If validation fails
     */
    public function fromJson(string $dtoClass, string $json): DataTransferObject
    {
        return $dtoClass::fromJson($json);
    }
}
