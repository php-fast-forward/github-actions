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

namespace FastForward\GitHubActions\Command;

use RuntimeException;
use FastForward\GitHubActions\GitHub\GitHubOutputWriter;
use FastForward\GitHubActions\Project\ProjectSurfaceDetector;
use JsonException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function Safe\getcwd;
use function Safe\json_encode;

#[AsCommand(
    name: 'php:detect-project',
    description: 'Detect Composer, PHPUnit, PHP source, test, and documentation surfaces.',
)]
final class PhpDetectProjectCommand extends Command
{
    /**
     * @param ProjectSurfaceDetector $detector
     * @param GitHubOutputWriter $githubOutputWriter
     */
    public function __construct(
        private readonly ProjectSurfaceDetector $detector,
        private readonly GitHubOutputWriter $githubOutputWriter,
    ) {
        parent::__construct();
    }

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this
            ->addOption(
                'working-dir',
                null,
                InputOption::VALUE_REQUIRED,
                'Repository working directory.',
                getcwd() ?: '.'
            )
            ->addOption('github-output', null, InputOption::VALUE_NONE, 'Write detected values to GITHUB_OUTPUT.')
            ->addOption('output-file', null, InputOption::VALUE_REQUIRED, 'Override the GitHub output file path.');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @throws JsonException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $workingDirectory = (string) $input->getOption('working-dir');
        $surface = $this->detector->detect($workingDirectory);

        if ($input->getOption('github-output')) {
            $this->writeGitHubOutputs($input, $surface->toGitHubOutputs());
        }

        $output->writeln(json_encode($surface->toArray(), \JSON_THROW_ON_ERROR | \JSON_PRETTY_PRINT));

        return Command::SUCCESS;
    }

    /**
     * @param array<string, string> $outputs
     * @param InputInterface $input
     */
    private function writeGitHubOutputs(InputInterface $input, array $outputs): void
    {
        $path = (string) ($input->getOption('output-file') ?: getenv('GITHUB_OUTPUT') ?: '');

        if ('' === $path) {
            throw new RuntimeException('GITHUB_OUTPUT is not available; pass --output-file to write command outputs.');
        }

        foreach ($outputs as $name => $value) {
            $this->githubOutputWriter->write($path, $name, $value);
        }
    }
}
