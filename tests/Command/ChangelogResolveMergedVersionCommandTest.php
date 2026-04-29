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

use FastForward\GitHubActions\Command\ChangelogResolveMergedVersionCommand;
use FastForward\GitHubActions\GitHub\GitHubOutputWriter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

use function Safe\file_get_contents;
use function Safe\mkdir;

#[CoversClass(ChangelogResolveMergedVersionCommand::class)]
#[CoversClass(GitHubOutputWriter::class)]
final class ChangelogResolveMergedVersionCommandTest extends TestCase
{
    /**
     * @return void
     */
    public function testItResolvesTheVersionFromAReleaseBranch(): void
    {
        $outputFile = $this->temporaryPath('github-output');
        $command = new ChangelogResolveMergedVersionCommand(new GitHubOutputWriter());
        $tester = new CommandTester($command);

        $exitCode = $tester->execute([
            'head-ref' => 'release/v0.1.0',
            '--github-output' => true,
            '--output-file' => $outputFile,
        ]);

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('0.1.0', $tester->getDisplay());
        self::assertSame("value=0.1.0\n", file_get_contents($outputFile));
    }

    /**
     * @return void
     */
    public function testItFailsWhenTheHeadRefDoesNotUseTheReleasePrefix(): void
    {
        $command = new ChangelogResolveMergedVersionCommand(new GitHubOutputWriter());
        $tester = new CommandTester($command);

        $exitCode = $tester->execute([
            'head-ref' => 'feature/not-a-release',
        ]);

        self::assertSame(1, $exitCode);
        self::assertStringContainsString('Failed to derive the release version', $tester->getDisplay());
    }

    /**
     * @param string $name
     *
     * @return string
     */
    private function temporaryPath(string $name): string
    {
        $directory = sys_get_temp_dir() . '/fast-forward-github-actions-' . bin2hex(random_bytes(8));

        mkdir($directory, 0o777, true);

        return $directory . '/' . $name;
    }
}
