<?php

$autoloadPaths = [
    __DIR__ . '/vendor/autoload.php',
    __DIR__ . '/../../../vendor/autoload.php',
    getcwd() . '/vendor/autoload.php',
];

$loader = null;
foreach ($autoloadPaths as $path) {
    if (file_exists($path)) {
        $loader = require $path;
        break;
    }
}

if (!$loader) {
    fwrite(STDERR, "Unable to find vendor/autoload.php. Please run composer install.\n");
    exit(1);
}

$loader->addPsr4('zfhassaan\\genlytics\\Tests\\', __DIR__ . '/tests/');

$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__ . '/tests'));
$testFiles = [];
foreach ($iterator as $file) {
    if ($file->isFile() && str_ends_with($file->getFilename(), 'Test.php')) {
        $testFiles[] = str_replace('\\', '/', $file->getPathname());
    }
}
sort($testFiles);

$passedArgs = array_slice($argv, 1);

$configPath = str_replace('\\', '/', __DIR__ . '/phpunit.xml');

// If neither -c nor --configuration is given, use the package phpunit.xml
$hasConfig = false;
foreach ($passedArgs as $arg) {
    if ($arg === '-c' || str_starts_with($arg, '--configuration')) {
        $hasConfig = true;
        break;
    }
}

$configArgs = [];
if (!$hasConfig && file_exists($configPath)) {
    $configArgs = ['-c', $configPath];
}

$phpunitArgs = array_merge(['phpunit'], $configArgs, $passedArgs, $testFiles);

exit((new PHPUnit\TextUI\Application)->run($phpunitArgs));
