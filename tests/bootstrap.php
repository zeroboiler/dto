<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Test bootstrap for ZeroBoiler DTO package
|--------------------------------------------------------------------------
*/

spl_autoload_register(function (string $class): void {
    $prefixes = [
        'ZeroBoiler\\DTO\\' => __DIR__ . '/../src/',
        'ZeroBoiler\\DTO\\Tests\\' => __DIR__ . '/',
    ];

    foreach ($prefixes as $prefix => $baseDir) {
        $len = strlen($prefix);
        if (strncmp($class, $prefix, $len) === 0) {
            $relative = substr($class, $len);
            $file     = $baseDir . str_replace('\\', '/', $relative) . '.php';

            if (file_exists($file)) {
                require $file;
            }
        }
    }
});
