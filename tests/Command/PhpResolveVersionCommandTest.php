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

namespace FastForward\GitHubActions\Tests\Command;

use FastForward\GitHubActions\Command\PhpResolveVersionCommand;
use FastForward\GitHubActions\GitHub\GitHubOutputWriter;
use FastForward\GitHubActions\Project\PhpVersionResolution;
use FastForward\GitHubActions\Project\PhpVersionResolver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

use function Safe\file_get_contents;
use function Safe\file_put_contents;
use function Safe\mkdir;

#[CoversClass(PhpResolveVersionCommand::class)]
#[CoversClass(GitHubOutputWriter::class)]
#[CoversClass(PhpVersionResolution::class)]
#[CoversClass(PhpVersionResolver::class)]
final class PhpResolveVersionCommandTest extends TestCase
{
    /**
     * @return void
     */
    public function testItResolvesThePhpVersionFromComposerLockPlatformOverrides(): void
    {
        $project = $this->temporaryDirectory();
        $outputFile = $this->temporaryDirectory() . '/github-output';

        file_put_contents($project . '/composer.lock', <<<'JSON'
            {
                "platform-overrides": {
                    "php": "8.4.12"
                }
            }
            JSON);

        $tester = new CommandTester(new PhpResolveVersionCommand(new PhpVersionResolver(), new GitHubOutputWriter()));

        $exitCode = $tester->execute([
            '--working-dir' => $project,
            '--github-output' => true,
            '--output-file' => $outputFile,
        ]);

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('"php-version": "8.4"', $tester->getDisplay());
        self::assertStringContainsString("php-version=8.4\n", file_get_contents($outputFile));
        self::assertStringContainsString(
            "php-version-source=composer.lock platform-overrides.php\n",
            file_get_contents($outputFile)
        );
        self::assertStringContainsString(
            "test-matrix={\"php-version\":[\"8.4\",\"8.5\"]}\n",
            file_get_contents($outputFile)
        );
    }

    /**
     * @return void
     */
    public function testItResolvesThePhpVersionFromComposerJsonRequirement(): void
    {
        $project = $this->temporaryDirectory();

        file_put_contents($project . '/composer.json', <<<'JSON'
            {
                "require": {
                    "php": "^8.3 || ^8.4"
                }
            }
            JSON);

        $tester = new CommandTester(new PhpResolveVersionCommand(new PhpVersionResolver(), new GitHubOutputWriter()));

        $exitCode = $tester->execute([
            '--working-dir' => $project,
        ]);

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('"php-version": "8.3"', $tester->getDisplay());
        self::assertStringContainsString('"php-version-source": "composer.json require.php"', $tester->getDisplay());
    }

    /**
     * @return void
     */
    public function testItFallsBackWhenComposerMetadataIsMissing(): void
    {
        $project = $this->temporaryDirectory();

        $tester = new CommandTester(new PhpResolveVersionCommand(new PhpVersionResolver(), new GitHubOutputWriter()));

        $exitCode = $tester->execute([
            '--working-dir' => $project,
        ]);

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('"php-version": "8.3"', $tester->getDisplay());
        self::assertStringContainsString('"php-version-source": "fallback"', $tester->getDisplay());
        self::assertStringContainsString('No reliable PHP version source was found.', $tester->getDisplay());
    }

    /**
     * @return string
     */
    private function temporaryDirectory(): string
    {
        $directory = sys_get_temp_dir() . '/fast-forward-github-actions-' . bin2hex(random_bytes(8));

        mkdir($directory, 0o777, true);

        return $directory;
    }
}
