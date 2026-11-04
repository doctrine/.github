<?php

declare(strict_types=1);

namespace Doctrine\Github\TargetBranchCheck\Comment;

use Doctrine\Github\TargetBranchCheck\Label\LabelConfiguration;
use Doctrine\Github\TargetBranchCheck\Label\LabelMatch;
use Doctrine\Github\TargetBranchCheck\Branch\BranchPolicy;
use Doctrine\Github\TargetBranchCheck\Release\ReleaseLine;
use Doctrine\Github\TargetBranchCheck\Branch\TargetTier;

final class CommentRenderer
{
    public const MARKER = '<!-- doctrine-target-branch-check -->';

    public function conflict(LabelMatch $match, ReleaseLine $target, BranchPolicy $policy): string
    {
        return implode("\n", [
            self::MARKER,
            sprintf('This pull request is labelled **%s**, but it targets `%s`.', $match->label, $target->name()),
            '',
            $this->expectedTarget($match->tier, $policy, $match->label),
            '',
            'To retarget it, choose **Edit** next to the title and select the appropriate base branch.',
            ...$this->regressionCaveat($match->tier, $target, $policy),
        ]);
    }

    public function missingTypeLabel(LabelConfiguration $labels): string
    {
        $formatted = array_map(
            static fn (string $label): string => '`' . $label . '`',
            $labels->allTargetLabels()->names,
        );

        return implode("\n", [
            self::MARKER,
            'This pull request has no type label, so its target branch cannot be checked.',
            '',
            sprintf('Add one of %s so it can be grouped correctly in the release notes.', implode(', ', $formatted)),
        ]);
    }

    private function expectedTarget(TargetTier $tier, BranchPolicy $policy, string $label): string
    {
        $expected = $tier->expectedLine($policy);

        if ($expected === null) {
            return sprintf(
                'Changes labelled **%s** should target the %s, but no such branch exists yet.',
                $label,
                $tier->branchDescription(),
            );
        }

        if ($tier === TargetTier::Patch) {
            return implode("\n", [
                sprintf('Changes labelled **%s** should target a patch release line, currently `%s`.', $label, $expected->name()),
                ...$this->olderPatchLines($policy),
            ]);
        }

        return sprintf(
            'Changes labelled **%s** should target the %s, `%s`.',
            $label,
            $tier->branchDescription(),
            $expected->name(),
        );
    }

    /** @return list<string> */
    private function olderPatchLines(BranchPolicy $policy): array
    {
        if ($policy->olderPatchLines === []) {
            return [];
        }

        $names = array_map(
            static fn (ReleaseLine $line): string => '`' . $line->name() . '`',
            $policy->olderPatchLines,
        );

        return [sprintf('Older maintained lines (%s) also receive fixes.', implode(', ', $names))];
    }

    /** @return list<string> */
    private function regressionCaveat(TargetTier $tier, ReleaseLine $target, BranchPolicy $policy): array
    {
        if ($tier !== TargetTier::Patch || $target->compare($policy->latestLine) <= 0) {
            return [];
        }

        return [
            '',
            'If this fixes a regression introduced in unreleased code, targeting that branch is correct and this comment can be ignored.',
        ];
    }
}
