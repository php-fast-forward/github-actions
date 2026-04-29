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

final readonly class ProjectSurface
{
    /**
     * @param bool $composerJson
     * @param bool $docsSource
     * @param bool $phpFiles
     * @param bool $phpunitConfig
     * @param bool $testFiles
     */
    public function __construct(
        public bool $composerJson,
        public bool $docsSource,
        public bool $phpFiles,
        public bool $phpunitConfig,
        public bool $testFiles,
    ) {}

    /**
     * @return bool
     */
    public function testable(): bool
    {
        return $this->composerJson && $this->phpunitConfig && $this->testFiles;
    }

    /**
     * @return bool
     */
    public function reportable(): bool
    {
        return $this->testable() && $this->docsSource && $this->phpFiles;
    }

    /**
     * @return array<string, string>
     */
    public function toGitHubOutputs(): array
    {
        return [
            'composer-json' => $this->format($this->composerJson),
            'docs-source' => $this->format($this->docsSource),
            'php-files' => $this->format($this->phpFiles),
            'phpunit-config' => $this->format($this->phpunitConfig),
            'test-files' => $this->format($this->testFiles),
            'testable' => $this->format($this->testable()),
            'reportable' => $this->format($this->reportable()),
        ];
    }

    /**
     * @return array<string, bool>
     */
    public function toArray(): array
    {
        return [
            'composer-json' => $this->composerJson,
            'docs-source' => $this->docsSource,
            'php-files' => $this->phpFiles,
            'phpunit-config' => $this->phpunitConfig,
            'test-files' => $this->testFiles,
            'testable' => $this->testable(),
            'reportable' => $this->reportable(),
        ];
    }

    /**
     * @param bool $value
     *
     * @return string
     */
    private function format(bool $value): string
    {
        return $value ? 'true' : 'false';
    }
}
