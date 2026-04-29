<?php

declare(strict_types=1);

/**
 * Symfony Console runtime for Fast Forward shared GitHub Actions automation.
 *
 * This file is part of fast-forward/github-actions project.
 *
 * @author   Felipe Sayao Lobato Abreu <github@mentordosnerds.com>
 * @license  https://opensource.org/licenses/MIT MIT License
 *
 * @see      https://github.com/php-fast-forward/github-actions
 * @see      https://github.com/php-fast-forward/github-actions/issues
 * @see      https://php-fast-forward.github.io/github-actions/
 * @see      https://datatracker.ietf.org/doc/html/rfc2119
 */

require __DIR__ . '/vendor/autoload.php';

use FastForward\DevTools\Path\WorkingProjectPathResolver;
use PhpCsFixer\Config;
use PhpCsFixer\Finder;

$rules = [
    'phpdoc_indent' => true,
    'phpdoc_order' => [
        'order' => ['param', 'return', 'throws'],
    ],
    'phpdoc_separation' => true,
    'phpdoc_trim' => true,
    'phpdoc_trim_consecutive_blank_line_separation' => true,
    'phpdoc_scalar' => true,
    'phpdoc_types' => true,
    'phpdoc_to_comment' => false,
    'phpdoc_add_missing_param_annotation' => true,
];

$docHeader = __DIR__ . '/.docheader';

if (file_exists($docHeader)) {
    $header = file_get_contents($docHeader);

    if (is_string($header)) {
        $header = preg_replace(
            ['!^/\*\*\n!', '! \*/!', '! \* ?!', '!%year%!', '!' . date('Y-Y') . '!'],
            [null, null, null, date('Y'), date('Y')],
            $header
        );

        $rules['header_comment'] = [
            'header' => trim((string) $header),
            'comment_type' => 'PHPDoc',
            'location' => 'after_declare_strict',
            'separate' => 'both',
        ];
    }
}

$finder = Finder::create()
    ->in([__DIR__])
    ->exclude(WorkingProjectPathResolver::TOOLING_EXCLUDED_DIRECTORIES);

return (new Config())
    ->setRiskyAllowed(false)
    ->setFinder($finder)
    ->setRules($rules);
