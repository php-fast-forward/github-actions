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

use JsonException;
use Throwable;

use function Safe\file_get_contents;
use function Safe\json_decode;
use function Safe\preg_match;
use function Safe\preg_match_all;

final class PhpVersionResolver
{
    private const string DEFAULT_PHP_VERSION = '8.3';

    /**
     * @var list<non-empty-string>
     */
    private const array SUPPORTED_MINORS = ['8.3', '8.4', '8.5'];

    /**
     * @param non-empty-string $workingDirectory
     */
    public function resolve(string $workingDirectory): PhpVersionResolution
    {
        [$resolved, $source] = $this->resolveFromLock($workingDirectory . '/composer.lock');

        if (null === $resolved) {
            [$resolved, $source] = $this->resolveFromJson($workingDirectory . '/composer.json');
        }

        if (null === $resolved) {
            return $this->fallback('No reliable PHP version source was found. Falling back to 8.3.');
        }

        if (! \in_array($resolved, self::SUPPORTED_MINORS, true)) {
            return $this->fallback(\sprintf(
                'Resolved PHP version %s from %s is outside the supported CI policy. Falling back to 8.3.',
                $resolved,
                $source ?? 'fallback',
            ));
        }

        return new PhpVersionResolution(
            phpVersion: $resolved,
            source: $source ?? 'fallback',
            warning: '',
            testMatrix: $this->matrixFrom($resolved),
        );
    }

    /**
     * @param string $composerLock
     *
     * @return array{0: string|null, 1: string|null}
     */
    private function resolveFromLock(string $composerLock): array
    {
        if (! is_file($composerLock)) {
            return [null, null];
        }

        try {
            $payload = json_decode(file_get_contents($composerLock), true, 512, \JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            return [null, 'composer.lock exists but could not be parsed'];
        }

        if (! \is_array($payload)) {
            return [null, 'composer.lock exists but could not be parsed'];
        }

        $platformOverrides = $payload['platform-overrides'] ?? [];

        if (! \is_array($platformOverrides) || ! \is_string($platformOverrides['php'] ?? null)) {
            return [null, null];
        }

        $resolved = $this->normalizeMinor($platformOverrides['php']);

        if (null !== $resolved) {
            return [$resolved, 'composer.lock platform-overrides.php'];
        }

        return [null, 'composer.lock platform-overrides.php is not a supported PHP version'];
    }

    /**
     * @param string $composerJson
     *
     * @return array{0: string|null, 1: string|null}
     */
    private function resolveFromJson(string $composerJson): array
    {
        if (! is_file($composerJson)) {
            return [null, 'composer.json does not exist'];
        }

        try {
            $payload = json_decode(file_get_contents($composerJson), true, 512, \JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return [null, 'composer.json could not be parsed'];
        }

        if (! \is_array($payload)) {
            return [null, 'composer.json could not be parsed'];
        }

        $configPlatformPhp = $payload['config']['platform']['php'] ?? null;

        if (\is_string($configPlatformPhp)) {
            $resolved = $this->normalizeMinor($configPlatformPhp);

            if (null !== $resolved) {
                return [$resolved, 'composer.json config.platform.php'];
            }

            return [null, 'composer.json config.platform.php is not a supported PHP version'];
        }

        $requirePhp = $payload['require']['php'] ?? null;

        if (\is_string($requirePhp)) {
            $resolved = $this->inferMinimumSupportedMinor($requirePhp);

            if (null !== $resolved) {
                return [$resolved, 'composer.json require.php'];
            }

            return [null, 'composer.json require.php could not be resolved safely'];
        }

        return [null, null];
    }

    /**
     * @param string $requirement
     *
     * @return string|null
     */
    private function inferMinimumSupportedMinor(string $requirement): ?string
    {
        $lowerBounds = [];

        foreach (explode('||', $requirement) as $clause) {
            $clauseLowerBound = $this->inferClauseLowerBound(trim($clause));

            if (null !== $clauseLowerBound) {
                $lowerBounds[] = $clauseLowerBound;
            }
        }

        if ([] === $lowerBounds) {
            return null;
        }

        usort($lowerBounds, version_compare(...));

        return $lowerBounds[0];
    }

    /**
     * @param string $clause
     *
     * @return string|null
     */
    private function inferClauseLowerBound(string $clause): ?string
    {
        preg_match_all('/(\^|~|>=|>|<=|<|==|=)?\s*v?(8\.\d+(?:\.\d+)?(?:\.\*)?)/', $clause, $matches, \PREG_SET_ORDER);

        $lowerBounds = [];

        foreach ($matches as $match) {
            $operator = $match[1] ?? '';
            $normalized = $this->normalizeMinor($match[2]);

            if (null === $normalized) {
                continue;
            }

            if (\in_array($operator, ['', '=', '==', '^', '~', '>='], true)) {
                $lowerBounds[] = $normalized;

                continue;
            }

            if ('>' === $operator && null !== ($nextMinor = $this->nextSupportedMinor($normalized))) {
                $lowerBounds[] = $nextMinor;
            }
        }

        if ([] === $lowerBounds) {
            return null;
        }

        usort($lowerBounds, version_compare(...));

        return $lowerBounds[array_key_last($lowerBounds)];
    }

    /**
     * @param string $version
     *
     * @return string|null
     */
    private function normalizeMinor(string $version): ?string
    {
        if (1 !== preg_match('/^\s*v?(8)\.(\d+)(?:\.\d+)?(?:\.\*)?\s*$/', $version, $matches)) {
            return null;
        }

        return \sprintf('%s.%s', $matches[1], $matches[2]);
    }

    /**
     * @param string $version
     *
     * @return string|null
     */
    private function nextSupportedMinor(string $version): ?string
    {
        $index = array_search($version, self::SUPPORTED_MINORS, true);

        if (false === $index) {
            return null;
        }

        $nextIndex = $index + 1;

        if (isset(self::SUPPORTED_MINORS[$nextIndex])) {
            return self::SUPPORTED_MINORS[$nextIndex];
        }

        [$major, $minor] = array_map(intval(...), explode('.', $version));

        return \sprintf('%d.%d', $major, $minor + 1);
    }

    /**
     * @param string $resolved
     *
     * @return list<non-empty-string>
     */
    private function matrixFrom(string $resolved): array
    {
        return array_values(array_filter(
            self::SUPPORTED_MINORS,
            static fn(string $version): bool => version_compare($version, $resolved, '>='),
        ));
    }

    /**
     * @param string $warning
     *
     * @return PhpVersionResolution
     */
    private function fallback(string $warning): PhpVersionResolution
    {
        return new PhpVersionResolution(
            phpVersion: self::DEFAULT_PHP_VERSION,
            source: 'fallback',
            warning: $warning,
            testMatrix: $this->matrixFrom(self::DEFAULT_PHP_VERSION),
        );
    }
}
