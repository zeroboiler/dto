<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Test bootstrap for ZeroBoiler DTO package
|--------------------------------------------------------------------------
|
| Registers a minimal Laravel container so that facades (Validator, etc.)
| resolve correctly during tests without a full application boot.
|
*/

spl_autoload_register(function (string $class): void {
    $prefixes = [
        'ZeroBoiler\\DTO\\' => __DIR__.'/../src/',
        'ZeroBoiler\\DTO\\Tests\\' => __DIR__.'/',
        'ZeroBoiler\\ValueObjects\\' => __DIR__.'/../../value-objects/src/',
    ];

    foreach ($prefixes as $prefix => $baseDir) {
        $len = strlen($prefix);
        if (strncmp($class, $prefix, $len) === 0) {
            $relative = substr($class, $len);
            $file = $baseDir.str_replace('\\', '/', $relative).'.php';

            if (file_exists($file)) {
                require $file;
            }
        }
    }
});

/*
|--------------------------------------------------------------------------
| Minimal Laravel container for facade resolution
|--------------------------------------------------------------------------
|
| Tests that exercise with() or fromArray(validate: true) need the
| Illuminate Validator facade.  We bind a lightweight container instead
| of booting a full Laravel application.
|
*/

use Illuminate\Container\Container;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Illuminate\Support\Facades\Facade;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Factory;

$container = new Container;

// Bind the validation factory so Validator::make() resolves correctly
$container->singleton(
    ValidationFactory::class,
    fn (): Factory => new Factory(
        new Translator(
            new ArrayLoader,
            'en'
        )
    )
);

// Also register the 'validator' alias for facade accessor resolution
$container->alias(ValidationFactory::class, 'validator');

// Set the container instance on the Facade base class
Facade::setFacadeApplication($container);
