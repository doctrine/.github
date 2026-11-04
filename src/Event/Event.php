<?php

declare(strict_types=1);

namespace Doctrine\Github\TargetBranchCheck\Event;

use RuntimeException;

final class Event
{
    /** @param array<string, mixed> $payload */
    private function __construct(private readonly array $payload)
    {
    }

    public static function fromFile(string $path): self
    {
        $contents = @file_get_contents($path);

        if ($contents === false) {
            throw new RuntimeException(sprintf('Unable to read event payload at %s.', $path));
        }

        $payload = json_decode($contents, associative: true, depth: 512, flags: JSON_THROW_ON_ERROR);

        if (! is_array($payload)) {
            throw new RuntimeException('The event payload is not an object.');
        }

        return new self($payload);
    }

    public function pullRequest(): ?PullRequest
    {
        $payload = $this->payload['pull_request'] ?? null;

        return is_array($payload) ? PullRequest::fromApiPayload($payload) : null;
    }
}
