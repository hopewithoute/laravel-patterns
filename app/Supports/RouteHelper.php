<?php

namespace App\Supports;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Helper class to auto-load route files from a directory.
 */
class RouteHelper
{
    /**
     * Auto-load all PHP files from a given directory recursively.
     *
     * @param  string  $directory  The directory to scan for route files
     */
    public static function loadRoutesFromDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                require $file->getPathname();
            }
        }
    }
}
