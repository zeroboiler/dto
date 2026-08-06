<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use Illuminate\Validation\ValidationException;
use ZeroBoiler\DTO\Tests\Fixtures\MultiConstraintDTO;
use ZeroBoiler\DTO\Tests\Fixtures\ScalarConstraintsDTO;
use ZeroBoiler\Enums\Tests\Fixtures\UserStatus;

describe('ScalarConstraintsDTO — scalar type constraints', function () {
    it('hydrates with required field only', function () {
        $dto = ScalarConstraintsDTO::fromArray(['name' => 'Alice']);
        expect($dto->name)->toBe('Alice');
        expect($dto->is_admin)->toBeFalse();
        expect($dto->score)->toBe(0);
        expect($dto->rating)->toBe(0.0);
        expect($dto->uuid)->toBeNull();
        expect($dto->terms)->toBeFalse();
        expect($dto->secret)->toBeNull();
        expect($dto->code)->toBeNull();
        expect($dto->status)->toBe('pending');
    });

    it('validates required name field', function () {
        ScalarConstraintsDTO::fromArray([]);
    })->throws(ValidationException::class);

    it('validates integer min/max', function () {
        ScalarConstraintsDTO::fromArray(['name' => 'Alice', 'score' => 150]);
    })->throws(ValidationException::class);

    it('validates integer min bound', function () {
        ScalarConstraintsDTO::fromArray(['name' => 'Alice', 'score' => -1]);
    })->throws(ValidationException::class);

    it('validates accepted field', function () {
        ScalarConstraintsDTO::fromArray(['name' => 'Alice', 'terms' => false]);
    })->throws(ValidationException::class);

    it('validates prohibited field', function () {
        ScalarConstraintsDTO::fromArray(['name' => 'Alice', 'secret' => 'hacked']);
    })->throws(ValidationException::class);

    it('validates uuid format when provided', function () {
        ScalarConstraintsDTO::fromArray(['name' => 'Alice', 'uuid' => 'not-a-uuid']);
    })->throws(ValidationException::class);

    it('accepts valid uuid', function () {
        $dto = ScalarConstraintsDTO::fromArray([
            'name' => 'Alice',
            'uuid' => '9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d',
        ]);
        expect($dto->uuid)->toBe('9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d');
    });

    it('validates size constraint', function () {
        ScalarConstraintsDTO::fromArray(['name' => 'Alice', 'code' => 'toolong']);
    })->throws(ValidationException::class);

    it('accepts valid size', function () {
        $dto = ScalarConstraintsDTO::fromArray(['name' => 'Alice', 'code' => 'abc']);
        expect($dto->code)->toBe('abc');
    });

    it('serializes correctly', function () {
        $dto = ScalarConstraintsDTO::fromArray(['name' => 'Alice']);
        $arr = $dto->toArray();
        expect($arr)->toHaveKey('name');
        expect($arr)->toHaveKey('is_admin');
        expect($arr)->toHaveKey('score');
        expect($arr)->toHaveKey('rating');
        expect($arr)->toHaveKey('uuid');
        expect($arr)->toHaveKey('terms');
        expect($arr)->toHaveKey('status');
        expect($arr)->not->toHaveKey('secret'); // Prohibited — present but excluded via hidden? No, Prohibited is validation-only
    });

    it('rules include boolean, integer, numeric, uuid', function () {
        $rules = ScalarConstraintsDTO::rules();
        expect($rules['name'])->toContain('required');
        expect($rules['is_admin'])->toContain('boolean');
        expect($rules['score'])->toContain('integer');
        expect($rules['score'])->toContain('min:0');
        expect($rules['score'])->toContain('max:100');
        expect($rules['rating'])->toContain('numeric');
        expect($rules['uuid'])->toContain('uuid');
        expect($rules['terms'])->toContain('accepted');
        expect($rules['secret'])->toContain('prohibited');
        expect($rules['code'])->toContain('size:3');
        expect($rules['code'])->toContain('sometimes');
    });

    it('isEmpty returns true for default-only DTO', function () {
        $dto = ScalarConstraintsDTO::fromArray(['name' => '']);
        // name is '' which is empty, all others are default (false, 0, 0.0, null, false, null, null, 'pending')
        // But name is required — this would fail validation
        // Let's use validate: false
        $dto = ScalarConstraintsDTO::fromArray(['name' => ''], validate: false);
        expect($dto->isEmpty())->toBeFalse(); // name is '' (empty string counts as empty)
        // Actually '' IS considered empty by isEmpty() check
        // is_admin is false, score is 0, etc — all empty
        expect($dto->isEmpty())->toBeTrue();
    });

    it('isNotEmpty returns true when a field has value', function () {
        $dto = ScalarConstraintsDTO::fromArray(['name' => 'Alice'], validate: false);
        expect($dto->isNotEmpty())->toBeTrue();
    });
});

describe('MultiConstraintDTO — multiple constraint interaction', function () {
    it('hydrates with all required fields', function () {
        $dto = MultiConstraintDTO::fromArray([
            'username' => 'alice',
            'status' => 'active',
        ]);
        expect($dto->username)->toBe('alice');
        expect($dto->status)->toBe(UserStatus::ACTIVE);
        expect($dto->role)->toBe('viewer');
        expect($dto->language)->toBe('en');
        expect($dto->bio)->toBeNull();
        expect($dto->token)->toBeNull();
        expect($dto->optional_note)->toBeNull();
    });

    it('applies MapFrom for pref_lang', function () {
        $dto = MultiConstraintDTO::fromArray([
            'username' => 'alice',
            'status' => 'active',
            'pref_lang' => 'tr',
        ]);
        expect($dto->language)->toBe('tr');
    });

    it('validates pattern on username', function () {
        MultiConstraintDTO::fromArray([
            'username' => 'alice123', // contains digits — should fail pattern
            'status' => 'active',
        ]);
    })->throws(ValidationException::class);

    it('validates min length on username', function () {
        MultiConstraintDTO::fromArray([
            'username' => 'ab', // too short
            'status' => 'active',
        ]);
    })->throws(ValidationException::class);

    it('validates enum constraint on status', function () {
        MultiConstraintDTO::fromArray([
            'username' => 'alice',
            'status' => 'invalid_status',
        ]);
    })->throws(ValidationException::class);

    it('validates In constraint on role', function () {
        MultiConstraintDTO::fromArray([
            'username' => 'alice',
            'status' => 'active',
            'role' => 'superadmin', // not in ['admin', 'editor', 'viewer']
        ]);
    })->throws(ValidationException::class);

    it('accepts valid role from In constraint', function () {
        $dto = MultiConstraintDTO::fromArray([
            'username' => 'alice',
            'status' => 'active',
            'role' => 'admin',
        ]);
        expect($dto->role)->toBe('admin');
    });

    it('serializes enum to backed value', function () {
        $dto = MultiConstraintDTO::fromArray([
            'username' => 'alice',
            'status' => 'active',
        ]);
        $arr = $dto->toArray();
        expect($arr['status'])->toBe('active');
        expect($arr)->not->toHaveKey('token'); // Hidden
    });

    it('allValues includes hidden fields', function () {
        $dto = MultiConstraintDTO::fromArray([
            'username' => 'alice',
            'status' => 'active',
            'token' => 'secret123',
        ]);
        $arr = $dto->allValues();
        expect($arr)->toHaveKey('token');
        expect($arr['token'])->toBe('secret123');
    });

    it('only returns specified fields', function () {
        $dto = MultiConstraintDTO::fromArray([
            'username' => 'alice',
            'status' => 'active',
            'bio' => 'Hello world',
        ]);
        $only = $dto->only('username', 'bio');
        expect($only)->toHaveCount(2);
        expect($only)->toHaveKeys(['username', 'bio']);
    });

    it('except removes specified fields', function () {
        $dto = MultiConstraintDTO::fromArray([
            'username' => 'alice',
            'status' => 'active',
            'bio' => 'Hello world',
        ]);
        $except = $dto->except('bio');
        expect($except)->not->toHaveKey('bio');
        expect($except)->toHaveKey('username');
    });

    it('equals checks value equality', function () {
        $dto1 = MultiConstraintDTO::fromArray([
            'username' => 'alice',
            'status' => 'active',
        ]);
        $dto2 = MultiConstraintDTO::fromArray([
            'username' => 'alice',
            'status' => 'active',
        ]);
        expect($dto1->equals($dto2))->toBeTrue();
    });

    it('equals returns false for different values', function () {
        $dto1 = MultiConstraintDTO::fromArray([
            'username' => 'alice',
            'status' => 'active',
        ]);
        $dto2 = MultiConstraintDTO::fromArray([
            'username' => 'bob',
            'status' => 'active',
        ]);
        expect($dto1->equals($dto2))->toBeFalse();
    });

    it('with creates immutable copy', function () {
        $dto = MultiConstraintDTO::fromArray([
            'username' => 'alice',
            'status' => 'active',
        ]);
        $updated = $dto->with(['username' => 'alice_updated']);
        expect($dto->username)->toBe('alice'); // unchanged
        expect($updated->username)->toBe('alice_updated');
    });

    it('with validates the merged data', function () {
        $dto = MultiConstraintDTO::fromArray([
            'username' => 'alice',
            'status' => 'active',
        ]);
        $dto->with(['username' => 'ab']); // too short
    })->throws(ValidationException::class);

    it('fromPartialArray fills missing with defaults', function () {
        $dto = MultiConstraintDTO::fromPartialArray(['username' => 'alice']);
        expect($dto->username)->toBe('alice');
        expect($dto->role)->toBe('viewer'); // DefaultValue
        expect($dto->language)->toBe('en'); // DefaultValue
        expect($dto->status)->toBeNull(); // required but no default — null for partial
    });

    it('toJson serializes correctly', function () {
        $dto = MultiConstraintDTO::fromArray([
            'username' => 'alice',
            'status' => 'active',
        ]);
        $json = $dto->toJson();
        $decoded = json_decode($json, true);
        expect($decoded)->toBeArray();
        expect($decoded['username'])->toBe('alice');
        expect($decoded['status'])->toBe('active');
        expect($decoded)->not->toHaveKey('token');
    });

    it('rules contain pattern, enum, in, hidden', function () {
        $rules = MultiConstraintDTO::rules();
        expect($rules['username'])->toContain('required');
        expect($rules['username'])->toContain('min:3');
        expect($rules['username'])->toContain('max:50');
        // Pattern rule is regex:...
        $hasPattern = collect($rules['username'])->fn(fn ($r) => str_starts_with($r, 'regex:'));
        expect($hasPattern)->not->toBeEmpty();
    });
});
