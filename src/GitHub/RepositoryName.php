<?php

declare(strict_types=1);

namespace Doctrine\Github\TargetBranchCheck\GitHub;

use RuntimeException;

final class RepositoryName
{
    public function __construct(
        public readonly string $owner,
        public readonly string $name,
    ) {
    }

    public static function fromString(string $repository): self
    {
        $parts = explode('/', $repository, 2);

        if (count($parts) !== 2 || $parts[0] === '' || $parts[1] === '') {
            throw new RuntimeException(sprintf('Invalid repository name "%s".', $repository));
        }

        return new self(owner: $parts[0], name: $parts[1]);
    }

    public function path(): string
    {
        return sprintf('/repos/%s/%s', $this->owner, $this->name);
    }
}
