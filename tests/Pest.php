<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use Illuminate\Container\Container;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Illuminate\Support\Facades\Validator;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Factory;

// Set up a minimal Laravel container to support VO validation in tests.
$container = Container::getInstance();
$loader = new ArrayLoader;
$translator = new Translator($loader, 'en');
$factory = new Factory($translator);
$container->instance(ValidationFactory::class, $factory);
$container->instance('validator', $factory);
Validator::swap($factory);

uses()
    ->in(__DIR__);
