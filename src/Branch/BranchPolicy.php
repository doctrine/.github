<?php

declare(strict_types=1);

namespace Doctrine\Github\TargetBranchCheck\Branch;

use Doctrine\Github\TargetBranchCheck\Release\ReleaseLine;
use Doctrine\Github\TargetBranchCheck\Release\ReleaseLines;
use Doctrine\Github\TargetBranchCheck\Release\ReleaseVersion;

final class BranchPolicy
{
    /** @param list<ReleaseLine> $olderPatchLines */
    public function __construct(
        public readonly ReleaseLine $latestLine,
        public readonly array $olderPatchLines,
        public readonly ?ReleaseLine $nextMinorLine,
        public readonly ?ReleaseLine $nextMajorLine,
    ) {
    }

    public static function from(ReleaseLines $lines, ReleaseVersion $latestVersion): self
    {
        $latestLine = $latestVersion->line();

        return new self(
            latestLine: $latestLine,
            olderPatchLines: $lines->olderThan($latestLine),
            nextMinorLine: $lines->nextMinorFor($latestLine),
            nextMajorLine: $lines->nextMajorFor($latestLine),
        );
    }
}
