<?php
/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use Illuminate\Container\Container;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Factory;

// Set up a minimal Laravel container to support VO validation in tests.
$container = Container::getInstance();
$loader = new ArrayLoader;
$translator = new Translator($loader, 'en');
$container->instance(ValidationFactory::class, new Factory($translator));

uses()
    ->in(__DIR__);
