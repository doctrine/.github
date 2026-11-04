<?php

declare(strict_types=1);

namespace Doctrine\Github\TargetBranchCheck\Release;

final class ReleaseVersion
{
    public function __construct(
        public readonly int $major,
        public readonly int $minor,
        public readonly int $patch,
    ) {
    }

    public static function fromTagName(string $tagName): ?self
    {
        if (preg_match('/^v?(\d+)\.(\d+)\.(\d+)$/', $tagName, $matches) !== 1) {
            return null;
        }

        return new self(major: (int) $matches[1], minor: (int) $matches[2], patch: (int) $matches[3]);
    }

    /** @param list<self> $versions */
    public static function highest(array $versions): ?self
    {
        $highest = null;

        foreach ($versions as $version) {
            if ($highest === null || $version->compare($highest) > 0) {
                $highest = $version;
            }
        }

        return $highest;
    }

    public function line(): ReleaseLine
    {
        return new ReleaseLine($this->major, $this->minor);
    }

    public function compare(self $other): int
    {
        if ($this->major !== $other->major) {
            return $this->major <=> $other->major;
        }

        if ($this->minor !== $other->minor) {
            return $this->minor <=> $other->minor;
        }

        return $this->patch <=> $other->patch;
    }
}
