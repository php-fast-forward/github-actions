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

final readonly class PhpVersionResolution
{
    /**
     * @param non-empty-string $phpVersion
     * @param non-empty-string $source
     * @param list<non-empty-string> $testMatrix
     * @param string $warning
     */
    public function __construct(
        public string $phpVersion,
        public string $source,
        public string $warning,
        public array $testMatrix,
    ) {}

    /**
     * @return array<string, string>
     */
    public function toGitHubOutputs(): array
    {
        return [
            'php-version' => $this->phpVersion,
            'php-version-source' => $this->source,
            'test-matrix' => $this->formatTestMatrix(),
            'warning' => $this->warning,
        ];
    }

    /**
     * @return array{php-version: string, php-version-source: string, test-matrix: array{php-version: list<string>}, warning: string}
     */
    public function toArray(): array
    {
        return [
            'php-version' => $this->phpVersion,
            'php-version-source' => $this->source,
            'test-matrix' => [
                'php-version' => $this->testMatrix,
            ],
            'warning' => $this->warning,
        ];
    }

    /**
     * @return string
     */
    private function formatTestMatrix(): string
    {
        return \sprintf('{"php-version":[%s]}', implode(',', array_map(
            static fn(string $version): string => \sprintf('"%s"', $version),
            $this->testMatrix,
        )));
    }
}
