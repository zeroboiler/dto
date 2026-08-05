<?php

/**
 * This file is part of ZeroBoiler, licensed under the proprietary license.
 */

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php85\Rector\Property\AddOverrideAttributeToOverriddenPropertiesRector;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__.'/src',
        __DIR__.'/tests',
    ]);

    $rectorConfig->skip([
        __DIR__.'/tests/Fixtures',
        // #[Override] cannot target properties (only methods) — PHP constraint
        AddOverrideAttributeToOverriddenPropertiesRector::class,
    ]);

    // PHP upgrades
    $rectorConfig->sets([
        LevelSetList::UP_TO_PHP_85,
        SetList::CODE_QUALITY,
        SetList::DEAD_CODE,
        SetList::EARLY_RETURN,
        SetList::INSTANCEOF,
        SetList::TYPE_DECLARATION,
        SetList::PRIVATIZATION,
    ]);

    // Auto-import
    $rectorConfig->importNames();
    $rectorConfig->importShortClasses(false);

    // Parallel
    $rectorConfig->parallel();
};
