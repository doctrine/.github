<?php

declare(strict_types=1);

namespace Doctrine\Github\TargetBranchCheck\Comment;

use Doctrine\Github\TargetBranchCheck\GitHub\GitHub;

final class CommentManager
{
    public function __construct(
        private readonly GitHub $github,
        private readonly int $issueNumber,
        private readonly string $commentAuthor = 'github-actions[bot]',
    ) {
    }

    public function write(string $body): void
    {
        $commentId = $this->findCommentId();

        if ($commentId === null) {
            $this->github->createComment($this->issueNumber, $body);

            return;
        }

        $this->github->updateComment($commentId, $body);
    }

    public function clear(): void
    {
        $commentId = $this->findCommentId();

        if ($commentId === null) {
            return;
        }

        $this->github->deleteComment($commentId);
    }

    private function findCommentId(): ?int
    {
        foreach ($this->github->comments($this->issueNumber) as $comment) {
            $commentId = $this->ownCommentId($comment);

            if ($commentId !== null) {
                return $commentId;
            }
        }

        return null;
    }

    /** @param array<string, mixed> $comment */
    private function ownCommentId(array $comment): ?int
    {
        $user = $comment['user'] ?? [];
        $login = is_array($user) ? ($user['login'] ?? '') : '';
        $body = $comment['body'] ?? '';

        if ($login !== $this->commentAuthor) {
            return null;
        }

        if (! is_string($body) || ! str_contains($body, CommentRenderer::MARKER)) {
            return null;
        }

        $id = $comment['id'] ?? null;

        return is_int($id) ? $id : null;
    }
}
