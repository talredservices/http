<?php

declare(strict_types=1);

/**
 * Expose the Talred public namespace without duplicating the Zolta HTTP
 * implementation. Existing applications continue to use Zolta\\Http; new
 * applications may import Talred\\Http during the compatibility period.
 */
if (! defined('TALRED_HTTP_COMPATIBILITY_LOADER_REGISTERED')) {
    define('TALRED_HTTP_COMPATIBILITY_LOADER_REGISTERED', true);

    spl_autoload_register(static function (string $class): void {
        $prefix = 'Talred\\Http\\';

        if (! str_starts_with($class, $prefix)) {
            return;
        }

        if (
            class_exists($class, false)
            || interface_exists($class, false)
            || trait_exists($class, false)
            || (function_exists('enum_exists') && enum_exists($class, false))
        ) {
            return;
        }

        $legacy = 'Zolta\\Http\\'.substr($class, strlen($prefix));
        $resolved = class_exists($legacy)
            || interface_exists($legacy)
            || trait_exists($legacy)
            || (function_exists('enum_exists') && enum_exists($legacy));

        if (! $resolved) {
            return;
        }

        class_alias($legacy, $class);
    }, true, false);
}
