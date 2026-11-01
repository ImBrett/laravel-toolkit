<?php
declare(strict_types=1);

// Root autoloader
require __DIR__ . '/../vendor/autoload.php';

// Unregister any Composer autoloaders registered from _impl/* (tool deps),
// except for the dedicated PHPUnit shim bundled in _impl/phpunit.
$loaders = spl_autoload_functions() ?: [];
$rootVendor = realpath(__DIR__ . '/../vendor') ?: (__DIR__ . '/../vendor');
foreach ($loaders as $loader) {
    if (!is_array($loader) || !$loader[0] instanceof \Composer\Autoload\ClassLoader) {
        continue;
    }
    $classLoader = $loader[0];

    $directories = [];
    foreach ([$classLoader->getPrefixesPsr4(), $classLoader->getPrefixes()] as $prefixMap) {
        foreach ($prefixMap as $dirs) {
            foreach ((array) $dirs as $dir) {
                $directories[] = $dir;
            }
        }
    }
    foreach ($classLoader->getFallbackDirsPsr4() as $dir) {
        $directories[] = $dir;
    }
    foreach ($classLoader->getFallbackDirs() as $dir) {
        $directories[] = $dir;
    }

    $keepRootVendor = false;
    $keepPhpUnitShim = false;

    foreach ($directories as $dir) {
        if (str_starts_with($dir, $rootVendor)) {
            $keepRootVendor = true;
        }

        if (str_contains($dir, DIRECTORY_SEPARATOR . '_impl' . DIRECTORY_SEPARATOR . 'phpunit' . DIRECTORY_SEPARATOR)) {
            $keepPhpUnitShim = true;
        }
    }

    if ($keepRootVendor || $keepPhpUnitShim) {
        continue;
    }

    $isToolLoader = false;
    foreach ($directories as $dir) {
        if (str_contains($dir, DIRECTORY_SEPARATOR . '_impl' . DIRECTORY_SEPARATOR)) {
            $isToolLoader = true;
            break;
        }
        if (str_contains($dir, DIRECTORY_SEPARATOR . 'packages' . DIRECTORY_SEPARATOR)
            && str_contains($dir, DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR)) {
            $isToolLoader = true;
            break;
        }
    }

    if ($isToolLoader) {
        spl_autoload_unregister($loader);
    }
}

// All package dev dependencies should be installed at the workspace level via the
// root composer.json. If a package needs additional bootstrap logic, it can do
// so from its own TestCase base class.
