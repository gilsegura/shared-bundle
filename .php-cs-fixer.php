<?php

declare(strict_types=1);

$finder = PhpCsFixer\Finder::create()
    ->in([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ]);

$config = new PhpCsFixer\Config();

return $config
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR12' => true,
        '@PHP8x5Migration' => true,
        '@Symfony' => true,

        'declare_strict_types' => true,
        'strict_comparison' => true,
        'strict_param' => true,

        'php_unit_method_casing' => ['case' => 'snake_case'],
    ])
    ->setFinder($finder);
