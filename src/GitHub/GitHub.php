<?php

declare(strict_types=1);

namespace Doctrine\Github\TargetBranchCheck\GitHub;

use Doctrine\Github\TargetBranchCheck\Event\PullRequest;
use Doctrine\Github\TargetBranchCheck\Project\ProjectMetadata;
use Doctrine\Github\TargetBranchCheck\Release\ReleaseLine;
use Doctrine\Github\TargetBranchCheck\Release\ReleaseVersion;

interface GitHub
{
    /** @return list<array<string, mixed>> */
    public function releases(): array;

    /** @return list<array<string, mixed>> */
    public function tags(): array;

    /** @return list<ReleaseLine> */
    public function releaseLines(): array;

    public function latestReleaseVersion(): ?ReleaseVersion;

    public function pullRequest(int $number): ?PullRequest;

    public function defaultBranch(): string;

    public function projectMetadata(string $ref): ?ProjectMetadata;

    /** @return list<array<string, mixed>> */
    public function comments(int $issueNumber): array;

    public function createComment(int $issueNumber, string $body): void;

    public function updateComment(int $commentId, string $body): void;

    public function deleteComment(int $commentId): void;
}
