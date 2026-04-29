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

namespace FastForward\GitHubActions\Console;

use FastForward\GitHubActions\Command\ChangelogResolveMergedVersionCommand;
use FastForward\GitHubActions\Command\PhpDetectProjectCommand;
use FastForward\GitHubActions\Command\SummaryWriteCommand;
use FastForward\GitHubActions\GitHub\GitHubOutputWriter;
use FastForward\GitHubActions\GitHub\StepSummaryWriter;
use FastForward\GitHubActions\Project\ProjectSurfaceDetector;
use Symfony\Component\Console\Application;

final class ConsoleApplicationFactory
{
    /**
     * @return Application
     */
    public static function create(): Application
    {
        $application = new Application('Fast Forward GitHub Actions', '0.1.x-dev');

        $githubOutputWriter = new GitHubOutputWriter();

        $application->addCommand(new ChangelogResolveMergedVersionCommand($githubOutputWriter));
        $application->addCommand(new PhpDetectProjectCommand(new ProjectSurfaceDetector(), $githubOutputWriter));
        $application->addCommand(new SummaryWriteCommand(new StepSummaryWriter()));

        return $application;
    }
}
