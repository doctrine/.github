<?php

declare(strict_types=1);

namespace Doctrine\Github\TargetBranchCheck\Project;

use Doctrine\Github\TargetBranchCheck\Release\ReleaseLine;
use RuntimeException;

final class ProjectMetadata
{
    /** @param list<ProjectVersion> $versions */
    private function __construct(private readonly array $versions)
    {
    }

    public static function fromJson(string $json): self
    {
        $data = json_decode($json, associative: true, depth: 512, flags: JSON_THROW_ON_ERROR);

        if (! is_array($data) || ! is_array($data['versions'] ?? null)) {
            throw new RuntimeException('The .doctrine-project.json file must define a "versions" list.');
        }

        $versions = [];

        foreach ($data['versions'] as $version) {
            $parsed = self::parseVersion($version);

            if ($parsed !== null) {
                $versions[] = $parsed;
            }
        }

        if ($versions === []) {
            throw new RuntimeException('The .doctrine-project.json file does not define any usable versions.');
        }

        return new self($versions);
    }

    public function currentLine(): ?ReleaseLine
    {
        foreach ($this->versions as $version) {
            if ($version->current) {
                return $version->line;
            }
        }

        return null;
    }

    public function legacyMinorLine(): ?ReleaseLine
    {
        $current = $this->currentLine();

        if ($current === null) {
            return null;
        }

        return $this->smallest($this->upcomingLinesWith(
            static fn (ReleaseLine $line): bool => $line->major === $current->major - 1,
        ));
    }

    /** @return list<ReleaseLine> */
    public function maintainedLines(): array
    {
        $lines = [];

        foreach ($this->versions as $version) {
            if ($version->maintained) {
                $lines[] = $version->line;
            }
        }

        return $lines;
    }

    public function oldestMaintainedLine(): ?ReleaseLine
    {
        $lines = $this->maintainedLines();
        $current = $this->currentLine();

        if ($current !== null) {
            $lines[] = $current;
        }

        return $this->smallest($lines);
    }

    /** @param callable(ReleaseLine): bool $predicate
     *  @return list<ReleaseLine>
     */
    private function upcomingLinesWith(callable $predicate): array
    {
        $lines = [];

        foreach ($this->versions as $version) {
            if (! $version->upcoming || ! $predicate($version->line)) {
                continue;
            }

            $lines[] = $version->line;
        }

        return $lines;
    }

    private static function parseVersion(mixed $version): ?ProjectVersion
    {
        if (! is_array($version)) {
            return null;
        }

        $branchName = $version['branchName'] ?? null;

        if (! is_string($branchName)) {
            return null;
        }

        $line = ReleaseLine::fromBranchName($branchName);

        if ($line === null) {
            return null;
        }

        return new ProjectVersion(
            line: $line,
            current: (bool) ($version['current'] ?? false),
            upcoming: (bool) ($version['upcoming'] ?? false),
            maintained: (bool) ($version['maintained'] ?? false),
        );
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
