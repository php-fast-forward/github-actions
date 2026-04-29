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

use FastForward\GitHubActions\Command\SummaryWriteCommand;
use FastForward\GitHubActions\GitHub\StepSummaryWriter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

use function Safe\file_get_contents;
use function Safe\file_put_contents;
use function Safe\mkdir;

#[CoversClass(SummaryWriteCommand::class)]
#[CoversClass(StepSummaryWriter::class)]
final class SummaryWriteCommandTest extends TestCase
{
    /**
     * @return void
     */
    public function testItAppendsMarkdownToTheSummaryFile(): void
    {
        $summaryFile = $this->temporaryPath('summary.md');
        $command = new SummaryWriteCommand(new StepSummaryWriter());
        $tester = new CommandTester($command);

        $exitCode = $tester->execute([
            'markdown' => '## Workflow Summary',
            '--summary-file' => $summaryFile,
        ]);

        self::assertSame(0, $exitCode);
        self::assertSame("## Workflow Summary\n", file_get_contents($summaryFile));
    }

    /**
     * @return void
     */
    public function testItReadsMarkdownFromAFile(): void
    {
        $markdownFile = $this->temporaryPath('input.md');
        $summaryFile = $this->temporaryPath('summary.md');
        file_put_contents($markdownFile, "## From File\n");

        $command = new SummaryWriteCommand(new StepSummaryWriter());
        $tester = new CommandTester($command);

        $exitCode = $tester->execute([
            '--file' => $markdownFile,
            '--summary-file' => $summaryFile,
        ]);

        self::assertSame(0, $exitCode);
        self::assertSame("## From File\n", file_get_contents($summaryFile));
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
