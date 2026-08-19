<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO;

use Illuminate\Http\Request;
use ZeroBoiler\DTO\Support\OpenApiSchemaGenerator;

/**
 * Runtime DTO helper — accessible via `DTO` facade or injected.
 *
 * Provides runtime access to DTO creation, validation, JSON hydration,
 * rules retrieval, and OpenAPI schema generation without directly calling
 * static methods on DTO classes.
 *
 *   $dto = $manager->make(CreateUserDTO::class, $data);
 *   $validated = $manager->validate(CreateUserDTO::class, $data);
 *   $schema = $manager->schema(CreateUserDTO::class);
 *
 * @see \ZeroBoiler\DTO\Facades\DTO For the facade proxy
 * @see \ZeroBoiler\DTO\DataTransferObject For the base DTO class
 */
final readonly class DTOManager
{
    /**
     * Validate data against a DTO class.
     *
     * @param  class-string<DataTransferObject>  $dtoClass  The DTO class to validate against
     * @param  array<string, mixed>  $data  The data to validate
     * @return array<string, mixed> Validated data
     *
     * @throws \Illuminate\Validation\ValidationException If validation fails
     * @throws \InvalidArgumentException If the class is not a DTO subclass
     */
    public function validate(string $dtoClass, array $data): array
    {
        $this->ensureDtoClass($dtoClass);

        return $dtoClass::validateArray($data);
    }

    /**
     * Create a DTO instance from an associative array.
     *
     * @param  class-string<DataTransferObject>  $dtoClass  The DTO class to instantiate
     * @param  array<string, mixed>  $data  The data to hydrate
     * @return DataTransferObject The hydrated DTO instance
     *
     * @throws \Illuminate\Validation\ValidationException If validation fails
     * @throws \InvalidArgumentException If the class is not a DTO subclass
     */
    public function make(string $dtoClass, array $data): DataTransferObject
    {
        $this->ensureDtoClass($dtoClass);

        return $dtoClass::fromArray($data);
    }

    /**
     * Create a DTO instance from a JSON string.
     *
     * @param  class-string<DataTransferObject>  $dtoClass  The DTO class to instantiate
     * @param  string  $json  JSON string to decode and hydrate
     * @return DataTransferObject The hydrated DTO instance
     *
     * @throws \ZeroBoiler\DTO\Exceptions\DTOException If JSON is invalid
     * @throws \Illuminate\Validation\ValidationException If validation fails
     * @throws \InvalidArgumentException If the class is not a DTO subclass
     */
    public function makeFromJson(string $dtoClass, string $json): DataTransferObject
    {
        $this->ensureDtoClass($dtoClass);

        return $dtoClass::fromJson($json);
    }

    /**
     * Alias for {@see makeFromJson()} — create a DTO from a JSON string.
     *
     * @param  class-string<DataTransferObject>  $dtoClass  The DTO class to instantiate
     * @param  string  $json  JSON string to decode and hydrate
     * @return DataTransferObject The hydrated DTO instance
     */
    public function fromJson(string $dtoClass, string $json): DataTransferObject
    {
        return $this->makeFromJson($dtoClass, $json);
    }

    /**
     * Create a DTO instance from a partial array (PATCH semantics).
     *
     * Only fields present in $data are validated and hydrated.
     * Missing fields fall back to their default values.
     *
     * @param  class-string<DataTransferObject>  $dtoClass  The DTO class to instantiate
     * @param  array<string, mixed>  $data  Partial data to hydrate
     * @return DataTransferObject The hydrated DTO instance
     *
     * @throws \Illuminate\Validation\ValidationException If validation fails on present fields
     * @throws \InvalidArgumentException If the class is not a DTO subclass
     */
    public function fromPartialArray(string $dtoClass, array $data): DataTransferObject
    {
        $this->ensureDtoClass($dtoClass);

        return $dtoClass::fromPartialArray($data);
    }

    /**
     * Create a DTO instance from a partial HTTP request (PATCH semantics).
     *
     * Only fields present in the request are validated and hydrated.
     *
     * @param  class-string<DataTransferObject>  $dtoClass  The DTO class to instantiate
     * @param  Request  $request  The HTTP request
     * @return DataTransferObject The hydrated DTO instance
     *
     * @throws \Illuminate\Validation\ValidationException If validation fails on present fields
     * @throws \InvalidArgumentException If the class is not a DTO subclass
     */
    public function fromPartialRequest(string $dtoClass, Request $request): DataTransferObject
    {
        $this->ensureDtoClass($dtoClass);

        return $dtoClass::fromPartialRequest($request);
    }

    /**
     * Get validation rules for a DTO class.
     *
     * @param  class-string<DataTransferObject>  $dtoClass  The DTO class to get rules for
     * @return array<string, array<int, mixed>> Validation rules
     *
     * @throws \InvalidArgumentException If the class is not a DTO subclass
     */
    public function rules(string $dtoClass): array
    {
        $this->ensureDtoClass($dtoClass);

        return $dtoClass::rules();
    }

    /**
     * Get action-scoped validation rules for a DTO class.
     *
     * @param  class-string<DataTransferObject>  $dtoClass  The DTO class
     * @param  string  $action  The action context (e.g., 'create', 'update')
     * @return array<string, array<int, mixed>> Action-scoped validation rules
     *
     * @throws \InvalidArgumentException If the class is not a DTO subclass
     */
    public function rulesFor(string $dtoClass, string $action): array
    {
        $this->ensureDtoClass($dtoClass);

        return $dtoClass::rulesFor($action);
    }

    /**
     * Generate an OpenAPI schema for a DTO class.
     *
     * @param  class-string<DataTransferObject>  $dtoClass  The DTO class to generate schema for
     * @return array<string, mixed> OpenAPI schema array
     *
     * @throws \InvalidArgumentException If the class is not a DTO subclass
     */
    public function schema(string $dtoClass): array
    {
        $this->ensureDtoClass($dtoClass);

        return OpenApiSchemaGenerator::generate($dtoClass);
    }

    /**
     * Ensure the given class is a valid DTO subclass.
     *
     * @param  string  $dtoClass  The class name to check
     *
     * @throws \InvalidArgumentException If the class is not a DataTransferObject subclass
     */
    private function ensureDtoClass(string $dtoClass): void
    {
        if (! is_subclass_of($dtoClass, DataTransferObject::class)) {
            throw new \InvalidArgumentException(
                "[{$dtoClass}] is not a subclass of DataTransferObject."
            );
        }
    }
}
