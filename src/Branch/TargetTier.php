<?php

declare(strict_types=1);

namespace Doctrine\Github\TargetBranchCheck\Branch;

use Doctrine\Github\TargetBranchCheck\Release\ReleaseLine;

enum TargetTier: string
{
    case Patch = 'patch';
    case Minor = 'minor';
    case Major = 'major';

    public function expectedLine(BranchPolicy $policy): ?ReleaseLine
    {
        return match ($this) {
            self::Patch => $policy->latestLine,
            self::Minor => $policy->nextMinorLine,
            self::Major => $policy->nextMajorLine,
        };
    }

    public function accepts(ReleaseLine $target, BranchPolicy $policy): bool
    {
        if ($this === self::Patch) {
            return $target->compare($policy->latestLine) <= 0;
        }

        $expected = $this->expectedLine($policy);

        return $expected !== null && $expected->equals($target);
    }

    public function branchDescription(): string
    {
        return match ($this) {
            self::Patch => 'patch release line',
            self::Minor => 'next minor branch',
            self::Major => 'next major branch',
        };
    }
}
