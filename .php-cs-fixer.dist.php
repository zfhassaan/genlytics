<?php

use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$finder = Finder::create()
    ->in(__DIR__)
    ->exclude(['vendor', 'node_modules']);

return (new Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR12' => true,
    ])
    ->setFinder($finder);
