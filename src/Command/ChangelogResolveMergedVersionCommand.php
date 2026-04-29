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
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'changelog:resolve-merged-version',
    description: 'Derive a release version from a merged release branch name.',
)]
final class ChangelogResolveMergedVersionCommand extends Command
{
    /**
     * @param GitHubOutputWriter $githubOutputWriter
     */
    public function __construct(
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
            ->addArgument('head-ref', InputArgument::REQUIRED, 'Merged pull request head ref.')
            ->addOption(
                'release-branch-prefix',
                null,
                InputOption::VALUE_REQUIRED,
                'Release branch prefix.',
                'release/v'
            )
            ->addOption('github-output', null, InputOption::VALUE_NONE, 'Write the resolved version to GITHUB_OUTPUT.')
            ->addOption('output-file', null, InputOption::VALUE_REQUIRED, 'Override the GitHub output file path.');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $headRef = (string) $input->getArgument('head-ref');
        $releaseBranchPrefix = (string) $input->getOption('release-branch-prefix');

        if ('' === $releaseBranchPrefix || ! str_starts_with($headRef, $releaseBranchPrefix)) {
            $io->error(\sprintf('Failed to derive the release version from "%s".', $headRef));

            return Command::FAILURE;
        }

        $version = substr($headRef, \strlen($releaseBranchPrefix));

        if ('' === $version) {
            $io->error(\sprintf('Release branch "%s" does not contain a version.', $headRef));

            return Command::FAILURE;
        }

        if ($input->getOption('github-output')) {
            $this->writeGitHubOutput($input, 'value', $version);
        }

        $output->writeln($version);

        return Command::SUCCESS;
    }

    /**
     * @param InputInterface $input
     * @param string $name
     * @param string $value
     *
     * @return void
     *
     * @throws RuntimeException
     */
    private function writeGitHubOutput(InputInterface $input, string $name, string $value): void
    {
        $path = (string) ($input->getOption('output-file') ?: getenv('GITHUB_OUTPUT') ?: '');

        if ('' === $path) {
            throw new RuntimeException('GITHUB_OUTPUT is not available; pass --output-file to write command outputs.');
        }

        $this->githubOutputWriter->write($path, $name, $value);
    }
}
