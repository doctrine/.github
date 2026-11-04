<?php

declare(strict_types=1);

namespace Doctrine\Github\TargetBranchCheck\Label;

final class Labels
{
    /** @param list<string> $names */
    public function __construct(public readonly array $names)
    {
    }

    /** @param list<string> $names */
    public static function fromNames(array $names): self
    {
        return new self(array_values(array_unique($names)));
    }

    public function firstMatching(self $candidates): ?string
    {
        foreach ($candidates->names as $candidate) {
            if (in_array($candidate, $this->names, true)) {
                return $candidate;
            }
        }

        return null;
    }

    public function merged(self ...$others): self
    {
        $names = $this->names;

        foreach ($others as $other) {
            $names = [...$names, ...$other->names];
        }

        return self::fromNames($names);
    }
}
