<?php
$finder = PhpCsFixer\Finder::create()
    ->in('./Classes', './Tests');

return PhpCsFixer\Config::create()
    ->setRules([
        '@PSR2' => true,
        'single_blank_line_at_eof' => false,
        'single_quote' => true,
        'array_syntax' => ['syntax' => 'short'],
        'align_multiline_comment' => ['comment_type' => 'phpdocs_like'],
    ])
    ->setFinder($finder);