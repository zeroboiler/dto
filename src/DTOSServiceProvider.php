<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

namespace ZeroBoiler\DTO;

use Illuminate\Support\ServiceProvider;
use ZeroBoiler\DTO\Console\Commands\MakeDtoSchemaCommand;
use ZeroBoiler\DTO\Console\Commands\MakeDtoTestCommand;

final class DTOSServiceProvider extends ServiceProvider
{
    #[\Override]
    public function register(): void
    {
        $this->app->singleton('zeroboiler.dto', fn (): DTOManager => new DTOManager);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                MakeDtoTestCommand::class,
                MakeDtoSchemaCommand::class,
            ]);
        }
    }
}
