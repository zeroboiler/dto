<?php

declare(strict_types=1);

namespace ZeroBoiler\DTO;

use Illuminate\Support\ServiceProvider;
use ZeroBoiler\DTO\Console\Commands\MakeDtoSchemaCommand;
use ZeroBoiler\DTO\Console\Commands\MakeDtoTestCommand;

final class DTOSServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('zeroboiler.dto', function () {
            return new DTOManager();
        });
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
