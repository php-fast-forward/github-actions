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

use InvalidArgumentException;
use Symfony\Component\Filesystem\Filesystem;

use function Safe\preg_match;
use function Safe\file_put_contents;

final readonly class GitHubOutputWriter
{
    /**
     * @param Filesystem $filesystem
     */
    public function __construct(
        private Filesystem $filesystem = new Filesystem(),
    ) {}

    /**
     * @param non-empty-string $name
     * @param string $path
     * @param string $value
     */
    public function write(string $path, string $name, string $value): void
    {
        if (0 === preg_match('/^[A-Za-z_][A-Za-z0-9_-]*$/', $name)) {
            throw new InvalidArgumentException(\sprintf('"%s" is not a valid GitHub Actions output name.', $name));
        }

        $directory = \dirname($path);

        if (! is_dir($directory)) {
            $this->filesystem->mkdir($directory);
        }

        file_put_contents($path, $this->format($name, $value), \FILE_APPEND | \LOCK_EX);
    }

    /**
     * @param string $name
     * @param string $value
     *
     * @return string
     */
    private function format(string $name, string $value): string
    {
        if (! str_contains($value, "\n")) {
            return \sprintf("%s=%s\n", $name, $value);
        }

        $delimiter = \sprintf('FAST_FORWARD_%s', hash('xxh128', $name . "\0" . $value));

        return \sprintf("%s<<%s\n%s\n%s\n", $name, $delimiter, $value, $delimiter);
    }
}
