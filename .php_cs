<?php

$finder = PhpCsFixer\Finder::create()
    ->in('.')
    ->exclude(['vendor', 'var'])
;

$config = new PhpCsFixer\Config();
return $config->setRules([
        '@Symfony' => true,
    ])
    ->setFinder($finder)
;
