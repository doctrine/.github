<?php

declare(strict_types=1);

namespace Doctrine\Github\TargetBranchCheck\Label;

use Doctrine\Github\TargetBranchCheck\Branch\TargetTier;
use RuntimeException;

final class LabelConfiguration
{
    public function __construct(
        public readonly Labels $patch,
        public readonly Labels $minor,
        public readonly Labels $major,
    ) {
    }

    public static function fromFile(
        string $path,
        string $patchOverride,
        string $minorOverride,
        string $majorOverride,
    ): self {
        $contents = @file_get_contents($path);

        if ($contents === false) {
            throw new RuntimeException(sprintf('Unable to read labels configuration at %s.', $path));
        }

        $config = json_decode($contents, associative: true, depth: 512, flags: JSON_THROW_ON_ERROR);

        if (! is_array($config)) {
            throw new RuntimeException('The labels configuration is not an object.');
        }

        self::validate($config);

        return new self(
            patch: self::resolve('patch', $config['targets']['patch'] ?? null, $patchOverride),
            minor: self::resolve('minor', $config['targets']['minor'] ?? null, $minorOverride),
            major: self::resolve('major', $config['targets']['major'] ?? null, $majorOverride),
        );
    }

    public function match(Labels $pullRequestLabels): ?LabelMatch
    {
        $label = $pullRequestLabels->firstMatching($this->major);

        if ($label !== null) {
            return new LabelMatch(TargetTier::Major, $label);
        }

        $label = $pullRequestLabels->firstMatching($this->minor);

        if ($label !== null) {
            return new LabelMatch(TargetTier::Minor, $label);
        }

        $label = $pullRequestLabels->firstMatching($this->patch);

        if ($label !== null) {
            return new LabelMatch(TargetTier::Patch, $label);
        }

        return null;
    }

    public function allTargetLabels(): Labels
    {
        return $this->patch->merged($this->minor, $this->major);
    }

    /** @param array<string, mixed> $config */
    private static function validate(array $config): void
    {
        $labels = $config['labels'] ?? null;

        if (! is_array($labels) || $labels === []) {
            throw new RuntimeException('The labels configuration must define a non-empty "labels" list.');
        }

        foreach ($labels as $label) {
            if (! is_string($label)) {
                throw new RuntimeException('The labels configuration must only contain string labels.');
            }
        }

        $targets = $config['targets'] ?? null;

        if (! is_array($targets)) {
            throw new RuntimeException('The labels configuration must define "targets".');
        }

        foreach (['patch', 'minor', 'major'] as $tier) {
            self::validateTargetList($targets, $tier, $labels);
        }
    }

    /** @param array<string, mixed> $targets
     *  @param list<string> $labels
     */
    private static function validateTargetList(array $targets, string $tier, array $labels): void
    {
        $tierLabels = $targets[$tier] ?? null;

        if (! is_array($tierLabels)) {
            return;
        }

        foreach ($tierLabels as $label) {
            if (! is_string($label) || ! in_array($label, $labels, true)) {
                throw new RuntimeException(sprintf('The labels configuration uses a non-canonical label: %s.', (string) $label));
            }
        }
    }

    private static function resolve(string $tier, mixed $configuredLabels, string $override): Labels
    {
        if (trim($override) !== '') {
            return Labels::fromNames(array_map('trim', explode(',', $override)));
        }

        if (! is_array($configuredLabels) || $configuredLabels === []) {
            throw new RuntimeException(sprintf('The labels configuration must define a non-empty "%s" target list.', $tier));
        }

        return Labels::fromNames(array_values($configuredLabels));
    }
}
