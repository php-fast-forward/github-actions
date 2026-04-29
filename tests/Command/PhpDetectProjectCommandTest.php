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

use FastForward\GitHubActions\Command\PhpDetectProjectCommand;
use FastForward\GitHubActions\GitHub\GitHubOutputWriter;
use FastForward\GitHubActions\Project\ProjectSurface;
use FastForward\GitHubActions\Project\ProjectSurfaceDetector;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

use function Safe\mkdir;
use function Safe\file_put_contents;
use function Safe\file_get_contents;

#[CoversClass(PhpDetectProjectCommand::class)]
#[CoversClass(GitHubOutputWriter::class)]
#[CoversClass(ProjectSurface::class)]
#[CoversClass(ProjectSurfaceDetector::class)]
final class PhpDetectProjectCommandTest extends TestCase
{
    /**
     * @return void
     */
    public function testItDetectsATestableAndReportableProject(): void
    {
        $project = $this->temporaryDirectory();
        $outputFile = $this->temporaryDirectory() . '/github-output';

        mkdir($project . '/docs', 0o777, true);
        mkdir($project . '/src', 0o777, true);
        mkdir($project . '/tests', 0o777, true);
        file_put_contents($project . '/composer.json', '{}');
        file_put_contents($project . '/phpunit.xml.dist', '<phpunit />');
        file_put_contents($project . '/docs/index.rst', 'Docs');
        file_put_contents($project . '/src/Example.php', '<?php');
        file_put_contents($project . '/tests/ExampleTest.php', '<?php');

        $command = new PhpDetectProjectCommand(new ProjectSurfaceDetector(), new GitHubOutputWriter());
        $tester = new CommandTester($command);

        $exitCode = $tester->execute([
            '--working-dir' => $project,
            '--github-output' => true,
            '--output-file' => $outputFile,
        ]);

        self::assertSame(0, $exitCode);
        self::assertJson($tester->getDisplay());
        self::assertStringContainsString("composer-json=true\n", file_get_contents($outputFile));
        self::assertStringContainsString("testable=true\n", file_get_contents($outputFile));
        self::assertStringContainsString("reportable=true\n", file_get_contents($outputFile));
    }

    /**
     * @return void
     */
    public function testItSkipsTestabilityWhenRequiredFilesAreMissing(): void
    {
        $project = $this->temporaryDirectory();
        file_put_contents($project . '/composer.json', '{}');

        $command = new PhpDetectProjectCommand(new ProjectSurfaceDetector(), new GitHubOutputWriter());
        $tester = new CommandTester($command);

        $exitCode = $tester->execute([
            '--working-dir' => $project,
        ]);

        self::assertSame(0, $exitCode);
        self::assertStringContainsString('"composer-json": true', $tester->getDisplay());
        self::assertStringContainsString('"testable": false', $tester->getDisplay());
        self::assertStringContainsString('"reportable": false', $tester->getDisplay());
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
