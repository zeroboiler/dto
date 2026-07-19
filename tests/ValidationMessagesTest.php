<?php
/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use ZeroBoiler\DTO\Attributes\Between;
use ZeroBoiler\DTO\Attributes\Email;
use ZeroBoiler\DTO\Attributes\Max;
use ZeroBoiler\DTO\Attributes\Min;
use ZeroBoiler\DTO\Attributes\Required;
use ZeroBoiler\DTO\Attributes\StartsWith;
use ZeroBoiler\DTO\DataTransferObject;
use ZeroBoiler\DTO\Support\DtoMetadataResolver;

describe('Issue #9: validation messages key generation', function (): void {
    it('generates correct message key for Email', function (): void {
        $messages = DtoMetadataResolver::resolve(MessageTestDTO::class)['messages'];

        expect($messages)->toHaveKey('email.email');
        expect($messages['email.email'])->toBe('Please enter a valid email');
    });

    it('generates correct message key for Required', function (): void {
        $messages = DtoMetadataResolver::resolve(MessageTestDTO::class)['messages'];

        expect($messages)->toHaveKey('email.required');
        expect($messages['email.required'])->toBe('Email is required');
    });

    it('generates correct message key for Max', function (): void {
        $messages = DtoMetadataResolver::resolve(MessageTestDTO::class)['messages'];

        expect($messages)->toHaveKey('name.max');
        expect($messages['name.max'])->toBe('Name too long');
    });

    it('generates correct message key for Min', function (): void {
        $messages = DtoMetadataResolver::resolve(MessageTestDTO::class)['messages'];

        expect($messages)->toHaveKey('name.min');
        expect($messages['name.min'])->toBe('Name too short');
    });

    it('generates correct message key for StartsWith', function (): void {
        $messages = DtoMetadataResolver::resolve(MessageTestDTO::class)['messages'];

        expect($messages)->toHaveKey('url.starts_with');
        expect($messages['url.starts_with'])->toBe('URL must start with https://');
    });

    it('generates correct message key for Between', function (): void {
        $messages = DtoMetadataResolver::resolve(MessageTestDTO::class)['messages'];

        expect($messages)->toHaveKey('count.between');
        expect($messages['count.between'])->toBe('Count must be between 1 and 10');
    });

    it('does not generate message keys for attributes without messages', function (): void {
        $messages = DtoMetadataResolver::resolve(NoMessageDTO::class)['messages'];

        // No custom messages set — messages array should be empty
        expect($messages)->toBe([]);
    });
});

class MessageTestDTO extends DataTransferObject
{
    public function __construct(
        #[Required(message: 'Email is required'), Email(message: 'Please enter a valid email')]
        public readonly string $email,

        #[Required, Min(2, message: 'Name too short'), Max(50, message: 'Name too long')]
        public readonly string $name,

        #[StartsWith('https://', message: 'URL must start with https://')]
        public readonly string $url,

        #[Between(1, 10, message: 'Count must be between 1 and 10')]
        public readonly int $count,
    ) {}
}

class NoMessageDTO extends DataTransferObject
{
    public function __construct(
        #[Required, Email]
        public readonly string $email,

        #[Min(2), Max(50)]
        public readonly string $name,
    ) {}
}
