<?php

$ruleset = new TwigCsFixer\Ruleset\Ruleset();
$ruleset->addStandard(new TwigCsFixer\Standard\TwigCsFixer());
$ruleset->overrideRule(new TwigCsFixer\Rules\Whitespace\IndentRule(useTab: true));

$finder = new TwigCsFixer\File\Finder();
$finder->in('tpl/');

$config = new TwigCsFixer\Config\Config();
$config->setRuleset($ruleset);
$config->setFinder($finder);

return $config;
