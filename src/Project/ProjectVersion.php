<?php

declare(strict_types=1);

namespace Doctrine\Github\TargetBranchCheck\Project;

use Doctrine\Github\TargetBranchCheck\Release\ReleaseLine;

final class ProjectVersion
{
    public function __construct(
        public readonly ReleaseLine $line,
        public readonly bool $current,
        public readonly bool $upcoming,
        public readonly bool $maintained,
    ) {
    }
}
