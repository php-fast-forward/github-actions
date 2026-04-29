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

namespace FastForward\GitHubActions\GitHub;

use Symfony\Component\Filesystem\Filesystem;

use function Safe\file_put_contents;

final readonly class StepSummaryWriter
{
    /**
     * @param Filesystem $filesystem
     */
    public function __construct(
        private Filesystem $filesystem = new Filesystem(),
    ) {}

    /**
     * @param string $path
     * @param string $markdown
     *
     * @return void
     */
    public function append(string $path, string $markdown): void
    {
        $directory = \dirname($path);

        if (! is_dir($directory)) {
            $this->filesystem->mkdir($directory);
        }

        file_put_contents($path, rtrim($markdown) . "\n", \FILE_APPEND | \LOCK_EX);
    }
}
