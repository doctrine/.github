<?php

declare(strict_types=1);

namespace Doctrine\Github\TargetBranchCheck\Command;

use Doctrine\Github\TargetBranchCheck\Check\TargetBranchCheck;
use Doctrine\Github\TargetBranchCheck\Comment\CommentRenderer;
use Doctrine\Github\TargetBranchCheck\Event\Event;
use Doctrine\Github\TargetBranchCheck\GitHub\GitHubClient;
use Doctrine\Github\TargetBranchCheck\GitHub\RepositoryName;
use Doctrine\Github\TargetBranchCheck\Label\LabelConfiguration;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpClient\HttpClient;

#[AsCommand(
    name: 'check-target-branch',
    description: 'Comments when a pull request\'s labels do not match its target branch',
)]
final class CheckTargetBranchCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addOption(
                'repository',
                mode: InputOption::VALUE_REQUIRED,
                description: 'Repository in owner/name form',
                default: getenv('GITHUB_REPOSITORY') ?: null,
            )
            ->addOption(
                'event-path',
                mode: InputOption::VALUE_REQUIRED,
                description: 'Path to the GitHub event payload',
                default: getenv('GITHUB_EVENT_PATH') ?: null,
            )
            ->addOption(
                'api-url',
                mode: InputOption::VALUE_REQUIRED,
                description: 'GitHub API base URL',
                default: getenv('GITHUB_API_URL') ?: 'https://api.github.com',
            )
            ->addOption(
                'labels-config',
                mode: InputOption::VALUE_REQUIRED,
                description: 'Path to labels.json',
                default: dirname(__DIR__, 2) . '/config/labels.json',
            )
            ->addOption(
                'patch-labels',
                mode: InputOption::VALUE_REQUIRED,
                description: 'Comma-separated patch labels',
                default: getenv('PATCH_LABELS') ?: '',
            )
            ->addOption(
                'minor-labels',
                mode: InputOption::VALUE_REQUIRED,
                description: 'Comma-separated minor labels',
                default: getenv('MINOR_LABELS') ?: '',
            )
            ->addOption(
                'major-labels',
                mode: InputOption::VALUE_REQUIRED,
                description: 'Comma-separated major labels',
                default: getenv('MAJOR_LABELS') ?: '',
            )
            ->addOption(
                'require-type-label',
                mode: InputOption::VALUE_NONE,
                description: 'Comment when none of the type labels are present',
            )
            ->addOption(
                'comment-author',
                mode: InputOption::VALUE_REQUIRED,
                description: 'Login that owns the managed comment',
                default: getenv('COMMENT_AUTHOR') ?: 'github-actions[bot]',
            )
            ->addOption(
                'fail-on-mismatch',
                mode: InputOption::VALUE_NONE,
                description: 'Exit non-zero when the labels do not match the target branch',
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $token = getenv('GITHUB_TOKEN');

        if (! is_string($token) || $token === '') {
            throw new RuntimeException('GITHUB_TOKEN is not set.');
        }

        $github = new GitHubClient(
            http: HttpClient::create(),
            repository: RepositoryName::fromString($this->requiredOption($input, 'repository')),
            apiUrl: $this->requiredOption($input, 'api-url'),
            token: $token,
        );

        $check = new TargetBranchCheck(
            event: Event::fromFile($this->requiredOption($input, 'event-path')),
            labels: LabelConfiguration::fromFile(
                $this->requiredOption($input, 'labels-config'),
                patchOverride: (string) $input->getOption('patch-labels'),
                minorOverride: (string) $input->getOption('minor-labels'),
                majorOverride: (string) $input->getOption('major-labels'),
            ),
            requireTypeLabel: (bool) $input->getOption('require-type-label') || getenv('REQUIRE_TYPE_LABEL') === 'true',
            github: $github,
            renderer: new CommentRenderer(),
            commentAuthor: (string) $input->getOption('comment-author'),
        );

        $valid = $check->run();

        if (! $valid && ((bool) $input->getOption('fail-on-mismatch') || getenv('FAIL_ON_MISMATCH') === 'true')) {
            $output->writeln('Target branch check failed.');

            return Command::FAILURE;
        }

        $output->writeln('Target branch check completed.', OutputInterface::VERBOSITY_VERBOSE);

        return Command::SUCCESS;
    }

    private function requiredOption(InputInterface $input, string $name): string
    {
        $value = $input->getOption($name);

        if (! is_string($value) || $value === '') {
            throw new RuntimeException(sprintf('The --%s option is required.', $name));
        }

        return $value;
    }
}
