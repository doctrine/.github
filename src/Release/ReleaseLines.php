<?php

declare(strict_types=1);

namespace Doctrine\Github\TargetBranchCheck\Release;

final class ReleaseLines
{
    /** @param list<ReleaseLine> $lines */
    private function __construct(private readonly array $lines)
    {
    }

    /** @param list<ReleaseLine> $lines */
    public static function fromLines(array $lines): self
    {
        return new self($lines);
    }

    /** @return list<ReleaseLine> */
    public function olderThan(ReleaseLine $line): array
    {
        return array_values(array_filter(
            $this->lines,
            static fn (ReleaseLine $candidate): bool => $candidate->compare($line) < 0,
        ));
    }

    public function nextMinorFor(ReleaseLine $line): ?ReleaseLine
    {
        return $this->smallest(array_values(array_filter(
            $this->lines,
            static fn (ReleaseLine $candidate): bool => $candidate->major === $line->major && $candidate->minor > $line->minor,
        )));
    }

    public function nextMajorFor(ReleaseLine $line): ?ReleaseLine
    {
        return $this->smallest(array_values(array_filter(
            $this->lines,
            static fn (ReleaseLine $candidate): bool => $candidate->major > $line->major && $candidate->minor === 0,
        )));
    }

    /** @param list<ReleaseLine> $lines */
    private function smallest(array $lines): ?ReleaseLine
    {
        $smallest = null;

        foreach ($lines as $line) {
            if ($smallest === null || $line->compare($smallest) < 0) {
                $smallest = $line;
            }
        }

        return $smallest;
    }
}
