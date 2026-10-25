<?php

$paths = [
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/../../../../vendor/autoload.php',
];

$loaded = false;
foreach ($paths as $path) {
    if (file_exists($path)) {
        $loader = require $path;
        $loaded = true;
        break;
    }
}

if (!$loaded) {
    die("Unable to locate vendor/autoload.php. Run 'composer install'.\n");
}

$loader->addPsr4('zfhassaan\\genlytics\\Tests\\', __DIR__ . '/');

