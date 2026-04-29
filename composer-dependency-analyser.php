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

use FastForward\DevTools\Config\ComposerDependencyAnalyserConfig;
use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;
use ShipMonk\ComposerDependencyAnalyser\Config\ErrorType;

return ComposerDependencyAnalyserConfig::configure(
    static function (Configuration $configuration): void {
        $configuration->ignoreErrorsOnPackage('fast-forward/dev-tools', [ErrorType::UNUSED_DEPENDENCY]);
    }
);
