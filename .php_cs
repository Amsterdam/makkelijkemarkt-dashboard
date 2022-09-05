<?php

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__)
    ->exclude(['vendor', 'var']);

$config = new PhpCsFixer\Config();
return $config->setRules([
    '@Symfony' => true,
])
    ->setFinder($finder);
