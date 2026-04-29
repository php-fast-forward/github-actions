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

namespace FastForward\GitHubActions\Project;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final class ProjectSurfaceDetector
{
    /**
     * @param non-empty-string $workingDirectory
     */
    public function detect(string $workingDirectory): ProjectSurface
    {
        return new ProjectSurface(
            composerJson: is_file($workingDirectory . '/composer.json'),
            docsSource: $this->hasAnyFile($workingDirectory . '/docs'),
            phpFiles: $this->hasPhpFileInAny($workingDirectory, ['app', 'bin', 'config', 'public', 'src', 'tests']),
            phpunitConfig: is_file($workingDirectory . '/phpunit.xml') || is_file(
                $workingDirectory . '/phpunit.xml.dist'
            ),
            testFiles: $this->hasPhpFile($workingDirectory . '/tests'),
        );
    }

    /**
     * @param list<string> $directories
     * @param string $workingDirectory
     */
    private function hasPhpFileInAny(string $workingDirectory, array $directories): bool
    {
        foreach ($directories as $directory) {
            if ($this->hasPhpFile($workingDirectory . '/' . $directory)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $directory
     *
     * @return bool
     */
    private function hasPhpFile(string $directory): bool
    {
        return $this->hasAnyFile($directory, 'php');
    }

    /**
     * @param string $directory
     * @param string|null $extension
     *
     * @return bool
     */
    private function hasAnyFile(string $directory, ?string $extension = null): bool
    {
        if (! is_dir($directory)) {
            return false;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if (! $file instanceof SplFileInfo) {
                continue;
            }

            if (! $file->isFile()) {
                continue;
            }

            if (null === $extension && '.DS_Store' !== $file->getFilename()) {
                return true;
            }

            if (null !== $extension && $file->getExtension() === $extension) {
                return true;
            }
        }

        return false;
    }
}
