<?php

declare(strict_types=1);

namespace Doctrine\Github\TargetBranchCheck\Label;

use Doctrine\Github\TargetBranchCheck\Branch\TargetTier;

final class LabelMatch
{
    public function __construct(
        public readonly TargetTier $tier,
        public readonly string $label,
    ) {
    }
}
