<?php

declare(strict_types=1);

namespace Doctrine\Github\TargetBranchCheck\GitHub;

use Doctrine\Github\TargetBranchCheck\Event\PullRequest;
use Doctrine\Github\TargetBranchCheck\Release\ReleaseLine;
use Doctrine\Github\TargetBranchCheck\Release\ReleaseVersion;
use RuntimeException;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

final class GitHubClient implements GitHub
{
    public function __construct(
        private readonly HttpClientInterface $http,
        private readonly RepositoryName $repository,
        private readonly string $apiUrl,
        private readonly string $token,
    ) {
    }

    /** @return list<array<string, mixed>> */
    public function releases(): array
    {
        return $this->paginate($this->repository->path() . '/releases');
    }

    /** @return list<array<string, mixed>> */
    public function tags(): array
    {
        return $this->paginate($this->repository->path() . '/tags');
    }

    /** @return list<ReleaseLine> */
    public function releaseLines(): array
    {
        $lines = [];

        foreach ($this->paginate($this->repository->path() . '/branches') as $branch) {
            $line = ReleaseLine::fromBranchName((string) ($branch['name'] ?? ''));

            if ($line !== null) {
                $lines[] = $line;
            }
        }

        return $lines;
    }

    public function latestReleaseVersion(): ?ReleaseVersion
    {
        $version = $this->latestReleaseVersionFromReleases();

        if ($version !== null) {
            return $version;
        }

        return $this->latestReleaseVersionFromTags();
    }

    public function pullRequest(int $number): ?PullRequest
    {
        $path = sprintf('%s/pulls/%d', $this->repository->path(), $number);
        $response = $this->http->request('GET', $this->apiUrl . $path, options: ['headers' => $this->headers()]);

        if ($response->getStatusCode() === 404) {
            return null;
        }

        $this->assertSuccessful($response, 'GET', $path);
        $payload = $response->toArray(throw: false);

        if (! is_array($payload)) {
            throw new RuntimeException('Expected an object from the GitHub API.');
        }

        return PullRequest::fromApiPayload($payload);
    }

    /** @return list<array<string, mixed>> */
    public function comments(int $issueNumber): array
    {
        return $this->paginate(sprintf('%s/issues/%d/comments', $this->repository->path(), $issueNumber));
    }

    public function createComment(int $issueNumber, string $body): void
    {
        $this->request('POST', sprintf('%s/issues/%d/comments', $this->repository->path(), $issueNumber), ['body' => $body]);
    }

    public function updateComment(int $commentId, string $body): void
    {
        $this->request('PATCH', sprintf('%s/issues/comments/%d', $this->repository->path(), $commentId), ['body' => $body]);
    }

    public function deleteComment(int $commentId): void
    {
        $this->request('DELETE', sprintf('%s/issues/comments/%d', $this->repository->path(), $commentId));
    }

    private function latestReleaseVersionFromReleases(): ?ReleaseVersion
    {
        $versions = [];

        foreach ($this->releases() as $release) {
            if (($release['draft'] ?? false) || ($release['prerelease'] ?? false)) {
                continue;
            }

            $version = ReleaseVersion::fromTagName((string) ($release['tag_name'] ?? ''));

            if ($version !== null) {
                $versions[] = $version;
            }
        }

        return ReleaseVersion::highest($versions);
    }

    private function latestReleaseVersionFromTags(): ?ReleaseVersion
    {
        $versions = [];

        foreach ($this->tags() as $tag) {
            $version = ReleaseVersion::fromTagName((string) ($tag['name'] ?? ''));

            if ($version !== null) {
                $versions[] = $version;
            }
        }

        return ReleaseVersion::highest($versions);
    }

    /** @param array<string, mixed>|null $body */
    private function request(string $method, string $path, ?array $body = null): void
    {
        $options = ['headers' => $this->headers()];

        if ($body !== null) {
            $options['json'] = $body;
        }

        $response = $this->http->request($method, $this->apiUrl . $path, options: $options);

        $this->assertSuccessful($response, $method, $path);
    }

    /** @return list<array<string, mixed>> */
    private function paginate(string $path): array
    {
        $items = [];
        $url = $this->apiUrl . $path . (str_contains($path, '?') ? '&' : '?') . 'per_page=100';

        while ($url !== null) {
            $response = $this->http->request('GET', $url, options: ['headers' => $this->headers()]);

            $this->assertSuccessful($response, 'GET', $url);

            $page = $response->toArray(throw: false);

            if (! is_array($page)) {
                throw new RuntimeException('Expected a list from the GitHub API.');
            }

            $items = [...$items, ...$page];
            $link = $response->getHeaders(throw: false)['link'][0] ?? null;
            $url = self::nextPageUrl($link);
        }

        return $items;
    }

    /** @return array<string, string> */
    private function headers(): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->token,
            'Accept' => 'application/vnd.github+json',
            'X-GitHub-Api-Version' => '2022-11-28',
            'User-Agent' => 'doctrine-target-branch-check',
        ];
    }

    private function assertSuccessful(ResponseInterface $response, string $method, string $path): void
    {
        $status = $response->getStatusCode();

        if ($status >= 200 && $status < 300) {
            return;
        }

        throw new RuntimeException(sprintf(
            'GitHub API %s %s failed with %d: %s',
            $method,
            $path,
            $status,
            $response->getContent(throw: false),
        ));
    }

    private static function nextPageUrl(?string $linkHeader): ?string
    {
        if ($linkHeader === null) {
            return null;
        }

        if (preg_match('/<([^>]+)>;\s*rel="next"/', $linkHeader, $matches) !== 1) {
            return null;
        }

        return $matches[1];
    }
}
