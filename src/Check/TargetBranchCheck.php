<?php

declare(strict_types=1);

namespace Doctrine\Github\TargetBranchCheck\Check;

use Doctrine\Github\TargetBranchCheck\Comment\CommentManager;
use Doctrine\Github\TargetBranchCheck\Comment\CommentRenderer;
use Doctrine\Github\TargetBranchCheck\Event\Event;
use Doctrine\Github\TargetBranchCheck\GitHub\GitHub;
use Doctrine\Github\TargetBranchCheck\Label\LabelConfiguration;
use Doctrine\Github\TargetBranchCheck\Branch\BranchPolicy;
use Doctrine\Github\TargetBranchCheck\Release\ReleaseLine;
use Doctrine\Github\TargetBranchCheck\Release\ReleaseLines;
use RuntimeException;

final class TargetBranchCheck
{
    public function __construct(
        private readonly Event $event,
        private readonly LabelConfiguration $labels,
        private readonly bool $requireTypeLabel,
        private readonly GitHub $github,
        private readonly CommentRenderer $renderer,
        private readonly string $commentAuthor = 'github-actions[bot]',
    ) {
    }

    public function run(): void
    {
        $eventPullRequest = $this->event->pullRequest();

        if ($eventPullRequest === null) {
            return;
        }

        $pullRequest = $this->github->pullRequest($eventPullRequest->number);

        if ($pullRequest === null || ! $pullRequest->isOpen() || $pullRequest->isMergeUp()) {
            return;
        }

        $match = $this->labels->match($pullRequest->labels);
        $comments = new CommentManager($this->github, $pullRequest->number, commentAuthor: $this->commentAuthor);

        if ($match === null) {
            $this->handleMissingTypeLabel($comments);

            return;
        }

        $latestVersion = $this->github->latestReleaseVersion();

        if ($latestVersion === null) {
            $comments->clear();

            return;
        }

        $policy = BranchPolicy::from(
            ReleaseLines::fromLines($this->github->releaseLines()),
            $latestVersion,
            metadata: $this->github->projectMetadata($this->github->defaultBranch()),
        );

        $targetLine = ReleaseLine::fromBranchName($pullRequest->baseRef);

        if ($targetLine === null) {
            throw new RuntimeException(sprintf(
                'Pull request base branch "%s" is not a release branch in the form "x.y.x".',
                $pullRequest->baseRef,
            ));
        }

        if ($match->tier->accepts($targetLine, $policy)) {
            $comments->clear();

            return;
        }

        $comments->write($this->renderer->conflict($match, $targetLine, $policy));
    }

    private function handleMissingTypeLabel(CommentManager $comments): void
    {
        if ($this->requireTypeLabel) {
            $comments->write($this->renderer->missingTypeLabel($this->labels));

            return;
        }

        $comments->clear();
    }
}
