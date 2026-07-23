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

        $this->configureDevCacheInvalidation();
        $this->registerCacheFlush();
    }

    /**
     * In local/testing environments, enable TTL-based metadata cache
     * invalidation so that changes to DTO classes are picked up
     * automatically without needing a manual cache flush (#3).
     *
     * Default TTL: 2 seconds — long enough to benefit from caching
     * within a single request, short enough to detect code changes
     * on the next page load.
     */
    private function configureDevCacheInvalidation(): void
    {
        if ($this->app->environment('local', 'testing')) {
            DataTransferObject::setMetadataCacheTtl(2.0);
        }
    }

    /**
     * Flush the DTO metadata cache at the end of each request in
     * long-lived processes (Octane, Swoole, RoadRunner).
     *
     * In standard PHP-FPM, the static cache dies with the process
     * at the end of every request. Long-lived runners keep it around,
     * which can cause stale metadata between deployments and unbounded
     * memory growth as more DTO classes are resolved.
     */
    private function registerCacheFlush(): void
    {
        // Laravel Octane — flush after each request
        $this->app['events']->listen('octane.terminate', function (): void {
            DataTransferObject::flushMetadataCache();
        });

        // Generic fallback — listen for the Laravel framework flush event
        // This works with Swoole/RoadRunner packages that dispatch this event
        $this->app['events']->listen('laravel.flush', function (): void {
            DataTransferObject::flushMetadataCache();
        });
    }
}
