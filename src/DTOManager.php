<?php

declare(strict_types=1);

namespace ZeroBoiler\DTO;

/**
 * Runtime DTO helper — accessible via `DTO` facade or injected.
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
     * @return DataTransferObject
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
        return \ZeroBoiler\DTO\Support\OpenApiSchemaGenerator::generate($dtoClass);
    }
}
