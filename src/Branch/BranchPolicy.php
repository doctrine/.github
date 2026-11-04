<?php

declare(strict_types=1);

namespace Doctrine\Github\TargetBranchCheck\Branch;

use Doctrine\Github\TargetBranchCheck\Project\ProjectMetadata;
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
        public readonly ?ProjectMetadata $metadata,
    ) {
    }

    public static function from(
        ReleaseLines $lines,
        ReleaseVersion $latestVersion,
        ?ProjectMetadata $metadata = null,
    ): self {
        $latestLine = $latestVersion->line();

        return new self(
            latestLine: $latestLine,
            olderPatchLines: self::olderPatchLines($lines, $latestLine, $metadata),
            nextMinorLine: $lines->nextMinorFor($latestLine),
            nextMajorLine: $lines->nextMajorFor($latestLine),
            metadata: $metadata,
        );
    }

    public function legacyMinorLine(): ?ReleaseLine
    {
        return $this->metadata?->legacyMinorLine();
    }

    public function oldestMaintainedLine(): ?ReleaseLine
    {
        return $this->metadata?->oldestMaintainedLine();
    }

    public function accepts(TargetTier $tier, ReleaseLine $target): bool
    {
        return match ($tier) {
            TargetTier::Patch => $this->acceptsPatch($target),
            TargetTier::Minor => $this->matchesAny([$this->nextMinorLine, $this->legacyMinorLine()], $target),
            TargetTier::Major => $this->matchesAny([$this->nextMajorLine], $target),
        };
    }

    private function acceptsPatch(ReleaseLine $target): bool
    {
        if ($this->metadata === null) {
            return $target->compare($this->latestLine) <= 0;
        }

        return $this->matchesAny(
            [
                $this->latestLine,
                $this->metadata->currentLine(),
                ...$this->metadata->maintainedLines(),
                $this->legacyMinorLine(),
            ],
            $target,
        );
    }

    /** @param list<?ReleaseLine> $lines */
    private function matchesAny(array $lines, ReleaseLine $target): bool
    {
        foreach ($lines as $line) {
            if ($line !== null && $line->equals($target)) {
                return true;
            }
        }

        return false;
    }

    /** @return list<ReleaseLine> */
    private static function olderPatchLines(
        ReleaseLines $lines,
        ReleaseLine $latestLine,
        ?ProjectMetadata $metadata,
    ): array {
        if ($metadata === null) {
            return $lines->olderThan($latestLine);
        }

        $linesToCheck = $metadata->maintainedLines();
        $current = $metadata->currentLine();

        if ($current !== null) {
            $linesToCheck[] = $current;
        }

        return array_values(array_filter(
            $linesToCheck,
            static fn (ReleaseLine $line): bool => $line->compare($latestLine) < 0,
        ));
    }
}
