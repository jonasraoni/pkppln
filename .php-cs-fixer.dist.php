<?php
require 'vendor/autoload.php';

$finder = (new PhpCsFixer\Finder())
    ->in(__DIR__)
    ->exclude('var')
    ->exclude('node_modules')
    ->exclude('vendor')
    ->exclude('web')
    ->exclude('vendor')
    ->exclude('data');

return (new PhpCsFixer\Config())
    ->setRules([
        '@DoctrineAnnotation' => true,
        '@PHPUnit84Migration:risky' => true,
        '@PSR12' => true,
        '@Symfony:risky' => true,
        '@PHP80Migration:risky' => true
    ])
    ->setFinder($finder)
    ->setRiskyAllowed(true)
    ->setCacheFile('var/cache/.php-cs-fixer.cache');
