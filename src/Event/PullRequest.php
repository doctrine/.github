<?php

declare(strict_types=1);

namespace Doctrine\Github\TargetBranchCheck\Event;

use Doctrine\Github\TargetBranchCheck\Label\Labels;
use RuntimeException;

final class PullRequest
{
    public function __construct(
        public readonly int $number,
        public readonly string $state,
        public readonly string $baseRef,
        public readonly string $headRef,
        public readonly Labels $labels,
    ) {
    }

    /** @param array<string, mixed> $payload */
    public static function fromApiPayload(array $payload): self
    {
        return new self(
            number: self::integer($payload, 'number'),
            state: self::string($payload, 'state'),
            baseRef: self::string(self::object($payload, 'base'), 'ref'),
            headRef: self::string(self::object($payload, 'head'), 'ref'),
            labels: Labels::fromNames(self::labelNames($payload)),
        );
    }

    public function isOpen(): bool
    {
        return $this->state === 'open';
    }

    public function isMergeUp(): bool
    {
        return str_contains($this->headRef, '-merge-up-into-');
    }

    /** @param array<string, mixed> $payload
     *  @return array<string, mixed>
     */
    private static function object(array $payload, string $key): array
    {
        $value = $payload[$key] ?? null;

        if (! is_array($value)) {
            throw new RuntimeException(sprintf('Missing object "%s" in pull request payload.', $key));
        }

        return $value;
    }

    /** @param array<string, mixed> $payload */
    private static function string(array $payload, string $key): string
    {
        $value = $payload[$key] ?? null;

        if (! is_string($value)) {
            throw new RuntimeException(sprintf('Missing string "%s" in pull request payload.', $key));
        }

        return $value;
    }

    /** @param array<string, mixed> $payload */
    private static function integer(array $payload, string $key): int
    {
        $value = $payload[$key] ?? null;

        if (! is_int($value)) {
            throw new RuntimeException(sprintf('Missing integer "%s" in pull request payload.', $key));
        }

        return $value;
    }

    /** @param array<string, mixed> $payload
     *  @return list<string>
     */
    private static function labelNames(array $payload): array
    {
        $labels = $payload['labels'] ?? [];

        if (! is_array($labels)) {
            throw new RuntimeException('Missing labels in pull request payload.');
        }

        $names = [];

        foreach ($labels as $label) {
            if (! is_array($label) || ! is_string($label['name'] ?? null)) {
                throw new RuntimeException('Invalid label in pull request payload.');
            }

            $names[] = $label['name'];
        }

        return $names;
    }
}
