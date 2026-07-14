<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use Illuminate\Container\Container;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Illuminate\Support\Facades\Facade;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Factory;

// Set up a minimal Laravel container to support VO validation and facade resolution in tests.
$container = Container::getInstance();
$loader = new ArrayLoader;
$translator = new Translator($loader, 'en');
$validationFactory = new Factory($translator);
$container->instance(ValidationFactory::class, $validationFactory);
$container->instance('validator', $validationFactory);
Facade::setFacadeApplication($container);

uses()
    ->in(__DIR__);
