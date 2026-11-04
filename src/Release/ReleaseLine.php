<?php

declare(strict_types=1);

namespace Doctrine\Github\TargetBranchCheck\Release;

final class ReleaseLine
{
    public function __construct(
        public readonly int $major,
        public readonly int $minor,
    ) {
    }

    public static function fromBranchName(string $branchName): ?self
    {
        if (preg_match('/^(\d+)\.(\d+)\.x$/', $branchName, $matches) !== 1) {
            return null;
        }

        return new self(major: (int) $matches[1], minor: (int) $matches[2]);
    }

    public function name(): string
    {
        return sprintf('%d.%d.x', $this->major, $this->minor);
    }

    public function compare(self $other): int
    {
        if ($this->major !== $other->major) {
            return $this->major <=> $other->major;
        }

        return $this->minor <=> $other->minor;
    }

    public function equals(self $other): bool
    {
        return $this->compare($other) === 0;
    }
}
