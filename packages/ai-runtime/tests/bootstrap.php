<?php

$autoloadCandidates = [
    __DIR__.'/../vendor/autoload.php',
    __DIR__.'/../../../vendor/autoload.php',
];

foreach ($autoloadCandidates as $autoloadPath) {
    if (! file_exists($autoloadPath)) {
        continue;
    }

    require_once $autoloadPath;

    break;
}

$paths = [
    __DIR__.'/Support',
    __DIR__.'/Fixtures',
];

foreach ($paths as $path) {
    if (! is_dir($path)) {
        continue;
    }

    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));

    foreach ($iterator as $file) {
        if (! $file->isFile() || $file->getExtension() !== 'php') {
            continue;
        }

        require_once $file->getPathname();
    }
}
