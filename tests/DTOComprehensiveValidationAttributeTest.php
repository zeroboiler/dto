<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO\Tests;

use ZeroBoiler\DTO\DtoCollection;
use ZeroBoiler\DTO\Tests\Fixtures\ComprehensiveValidationDTO;

describe('ComprehensiveValidationDTO — rules generation', function () {
    it('generates rules for all properties', function () {
        $rules = ComprehensiveValidationDTO::rules();
        expect($rules)->toBeArray()->not->toBeEmpty();
        expect($rules)->toHaveKey('email');
        expect($rules)->toHaveKey('username');
        expect($rules)->toHaveKey('code');
        expect($rules)->toHaveKey('isActive');
        expect($rules)->toHaveKey('score');
        expect($rules)->toHaveKey('rating');
        expect($rules)->toHaveKey('role');
        expect($rules)->toHaveKey('website');
        expect($rules)->toHaveKey('uuid');
        expect($rules)->toHaveKey('metadata');
        expect($rules)->toHaveKey('tags');
        expect($rules)->toHaveKey('phone');
        expect($rules)->toHaveKey('domain');
        expect($rules)->toHaveKey('bannedField');
        expect($rules)->toHaveKey('termsAccepted');
        expect($rules)->toHaveKey('spamDeclined');
        expect($rules)->toHaveKey('password');
        expect($rules)->toHaveKey('emailToken');
        expect($rules)->toHaveKey('adminSecret');
    });

    it('includes required rule for email', function () {
        $rules = ComprehensiveValidationDTO::rules();
        expect($rules['email'])->toContain('required');
        expect($rules['email'])->toContain('email');
    });

    it('includes required + min + max for username', function () {
        $rules = ComprehensiveValidationDTO::rules();
        expect($rules['username'])->toContain('required');
        expect($rules['username'])->toContain('min:3');
        expect($rules['username'])->toContain('max:100');
    });

    it('includes regex for code', function () {
        $rules = ComprehensiveValidationDTO::rules();
        expect($rules['code'])->toContain('regex:/^[A-Z]{3}-\\d{4}$/');
    });

    it('includes boolean rule for isActive', function () {
        $rules = ComprehensiveValidationDTO::rules();
        expect($rules['isActive'])->toContain('boolean');
    });

    it('includes integer + min + max for score', function () {
        $rules = ComprehensiveValidationDTO::rules();
        expect($rules['score'])->toContain('integer');
        expect($rules['score'])->toContain('min:0');
        expect($rules['score'])->toContain('max:100');
    });

    it('includes numeric for rating', function () {
        $rules = ComprehensiveValidationDTO::rules();
        expect($rules['rating'])->toContain('numeric');
    });

    it('includes in rule for role', function () {
        $rules = ComprehensiveValidationDTO::rules();
        expect($rules['role'])->toContain('in:admin,editor,viewer');
    });

    it('includes url rule for website', function () {
        $rules = ComprehensiveValidationDTO::rules();
        expect($rules['website'])->toContain('url');
    });

    it('includes uuid rule for uuid', function () {
        $rules = ComprehensiveValidationDTO::rules();
        expect($rules['uuid'])->toContain('uuid');
    });

    it('includes json rule for metadata', function () {
        $rules = ComprehensiveValidationDTO::rules();
        expect($rules['metadata'])->toContain('json');
    });

    it('includes starts_with for phone', function () {
        $rules = ComprehensiveValidationDTO::rules();
        expect($rules['phone'])->toContain('starts_with:+');
    });

    it('includes ends_with for domain', function () {
        $rules = ComprehensiveValidationDTO::rules();
        expect($rules['domain'])->toContain('ends_with:.com');
    });

    it('includes prohibited for bannedField', function () {
        $rules = ComprehensiveValidationDTO::rules();
        expect($rules['bannedField'])->toContain('prohibited');
    });

    it('includes present for optionalPresent', function () {
        $rules = ComprehensiveValidationDTO::rules();
        expect($rules['optionalPresent'])->toContain('present');
    });

    it('includes same rule for passwordConfirmation', function () {
        $rules = ComprehensiveValidationDTO::rules();
        expect($rules['passwordConfirmation'])->toContain('same:password');
    });

    it('includes different rule for notEmail', function () {
        $rules = ComprehensiveValidationDTO::rules();
        expect($rules['notEmail'])->toContain('different:email');
    });

    it('includes accepted for termsAccepted', function () {
        $rules = ComprehensiveValidationDTO::rules();
        expect($rules['termsAccepted'])->toContain('accepted');
    });

    it('includes declined for spamDeclined', function () {
        $rules = ComprehensiveValidationDTO::rules();
        expect($rules['spamDeclined'])->toContain('declined');
    });

    it('includes confirmed for password', function () {
        $rules = ComprehensiveValidationDTO::rules();
        expect($rules['password'])->toContain('confirmed');
    });

    it('includes required_with for emailToken', function () {
        $rules = ComprehensiveValidationDTO::rules();
        expect($rules['emailToken'])->toContain('required_with:email');
    });

    it('includes required_with_all for emailAndNameToken', function () {
        $rules = ComprehensiveValidationDTO::rules();
        expect($rules['emailAndNameToken'])->toContain('required_with_all:email,username');
    });

    it('includes required_without for fallbackContact', function () {
        $rules = ComprehensiveValidationDTO::rules();
        expect($rules['fallbackContact'])->toContain('required_without:website');
    });

    it('includes required_without_all for primaryContact', function () {
        $rules = ComprehensiveValidationDTO::rules();
        expect($rules['primaryContact'])->toContain('required_without_all:website,phone');
    });

    it('includes required_if for adminSecret', function () {
        $rules = ComprehensiveValidationDTO::rules();
        expect($rules['adminSecret'])->toContain('required_if:role,admin');
    });

    it('includes size rule for tags', function () {
        $rules = ComprehensiveValidationDTO::rules();
        expect($rules['tags'])->toContain('size:5');
    });
});

describe('ComprehensiveValidationDTO — hydration and serialization', function () {
    it('hydrates from array without validation', function () {
        $dto = ComprehensiveValidationDTO::fromArray([
            'email' => 'test@example.com',
            'username' => 'johndoe',
        ], validate: false);

        expect($dto)->toBeInstanceOf(ComprehensiveValidationDTO::class);
        expect($dto->email)->toBe('test@example.com');
        expect($dto->username)->toBe('johndoe');
        expect($dto->isActive)->toBeTrue();
        expect($dto->status)->toBe('draft');
        expect($dto->tags)->toBe([]);
    });

    it('respects MapFrom for source key aliasing', function () {
        $dto = ComprehensiveValidationDTO::fromArray([
            'email' => 'test@example.com',
            'username' => 'johndoe',
            'source_field' => 'mapped_value',
        ], validate: false);

        expect($dto->mappedField)->toBe('mapped_value');
    });

    it('serializes to array excluding hidden fields', function () {
        $dto = ComprehensiveValidationDTO::fromArray([
            'email' => 'test@example.com',
            'username' => 'johndoe',
            'secret' => 'super-secret',
        ], validate: false);

        $arr = $dto->toArray();
        expect($arr)->not->toHaveKey('secret');
        expect($arr['email'])->toBe('test@example.com');
    });

    it('allValues includes hidden fields', function () {
        $dto = ComprehensiveValidationDTO::fromArray([
            'email' => 'test@example.com',
            'username' => 'johndoe',
            'secret' => 'super-secret',
        ], validate: false);

        $all = $dto->allValues();
        expect($all)->toHaveKey('secret');
        expect($all['secret'])->toBe('super-secret');
    });

    it('serializes to valid JSON', function () {
        $dto = ComprehensiveValidationDTO::fromArray([
            'email' => 'test@example.com',
            'username' => 'johndoe',
        ], validate: false);

        expect($dto->toJson())->toBeJson();
    });

    it('supports only() field filtering', function () {
        $dto = ComprehensiveValidationDTO::fromArray([
            'email' => 'test@example.com',
            'username' => 'johndoe',
        ], validate: false);

        $only = $dto->only('email');
        expect($only)->toBe(['email' => 'test@example.com']);
    });

    it('supports except() field exclusion', function () {
        $dto = ComprehensiveValidationDTO::fromArray([
            'email' => 'test@example.com',
            'username' => 'johndoe',
        ], validate: false);

        $except = $dto->except('username');
        expect($except)->not->toHaveKey('username');
        expect($except)->toHaveKey('email');
    });

    it('supports equals() value comparison', function () {
        $data = ['email' => 'test@example.com', 'username' => 'johndoe'];
        $dto1 = ComprehensiveValidationDTO::fromArray($data, validate: false);
        $dto2 = ComprehensiveValidationDTO::fromArray($data, validate: false);

        expect($dto1->equals($dto2))->toBeTrue();
    });

    it('supports immutable update with()', function () {
        $dto = ComprehensiveValidationDTO::fromArray([
            'email' => 'test@example.com',
            'username' => 'johndoe',
        ], validate: false);

        $updated = $dto->with(['status' => 'published']);
        expect($updated->status)->toBe('published');
        expect($dto->status)->toBe('draft'); // original unchanged
    });

    it('supports fromPartialArray for partial updates', function () {
        $dto = ComprehensiveValidationDTO::fromPartialArray([
            'username' => 'newname',
        ], validate: false);

        expect($dto->username)->toBe('newname');
        // email should get empty value from type inference (string → '')
    });

    it('JSON round-trip preserves data', function () {
        $dto = ComprehensiveValidationDTO::fromArray([
            'email' => 'test@example.com',
            'username' => 'johndoe',
            'role' => 'admin',
        ], validate: false);

        $json = $dto->toJson();
        $restored = ComprehensiveValidationDTO::fromJson($json, validate: false);

        expect($restored->email)->toBe('test@example.com');
        expect($restored->username)->toBe('johndoe');
        expect($restored->role)->toBe('admin');
    });

    it('rulesFor returns same as rules for default action', function () {
        $rules = ComprehensiveValidationDTO::rules();
        $rulesFor = ComprehensiveValidationDTO::rulesFor('create');
        expect($rules)->toBe($rulesFor);
    });
});
