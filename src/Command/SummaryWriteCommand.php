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
use FastForward\GitHubActions\GitHub\StepSummaryWriter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use function Safe\file_get_contents;
use function Safe\stream_get_contents;

#[AsCommand(name: 'summary:write', description: 'Append Markdown to the GitHub Actions step summary.',)]
final class SummaryWriteCommand extends Command
{
    /**
     * @param StepSummaryWriter $summaryWriter
     */
    public function __construct(
        private readonly StepSummaryWriter $summaryWriter,
    ) {
        parent::__construct();
    }

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this
            ->addArgument('markdown', InputArgument::OPTIONAL, 'Markdown content. Reads STDIN when omitted.')
            ->addOption('file', null, InputOption::VALUE_REQUIRED, 'Read Markdown content from a file.')
            ->addOption(
                'summary-file',
                null,
                InputOption::VALUE_REQUIRED,
                'Override the GitHub step summary file path.'
            );
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
        $summaryFile = (string) ($input->getOption('summary-file') ?: getenv('GITHUB_STEP_SUMMARY') ?: '');

        if ('' === $summaryFile) {
            $io->error('GITHUB_STEP_SUMMARY is not available; pass --summary-file to write a summary.');

            return Command::FAILURE;
        }

        $markdown = $this->resolveMarkdown($input);

        if ('' === trim($markdown)) {
            $io->note('No summary content supplied.');

            return Command::SUCCESS;
        }

        $this->summaryWriter->append($summaryFile, $markdown);
        $io->success(\sprintf('Summary appended to %s.', $summaryFile));

        return Command::SUCCESS;
    }

    /**
     * @param InputInterface $input
     *
     * @return string
     *
     * @throws RuntimeException
     */
    private function resolveMarkdown(InputInterface $input): string
    {
        $file = (string) ($input->getOption('file') ?: '');
        $markdown = $input->getArgument('markdown');

        if ('' !== $file) {
            $contents = file_get_contents($file);

            if (! \is_string($contents)) {
                throw new RuntimeException(\sprintf('Could not read summary file "%s".', $file));
            }

            return $contents;
        }

        if (\is_string($markdown)) {
            return $markdown;
        }

        $stdin = stream_get_contents(\STDIN);

        return \is_string($stdin) ? $stdin : '';
    }
}
