# Doctrine templates 

This repository is used as a reference for other Doctrine repositories, in
order to reduce differences between them.

## Workflow templates

The workflow templates of this repository can be set up by following the steps
described in [the official docs][Github docs about sharing workflows].

[Github docs about sharing workflows]: https://docs.github.com/en/actions/learn-github-actions/sharing-workflows-with-your-organization#using-a-workflow-template

## Automation

Shared automation lives in `src/`, with `bin/check-target-branch` as its entry
point. It is a Symfony Console command and requires PHP 8.1+ and Composer, both
preinstalled on GitHub-hosted runners. Workflows clone this repository at the
commit that defines the job, so a pinned workflow version always runs matching
code and configuration.

When the target repository contains a `.doctrine-project.json`, its `versions`
flags identify the maintained, upcoming, and legacy-minor branches. The
release-derived branch still provides the current patch line, since the metadata
can lag behind the default branch.

### Target branch check

```bash
composer install
GITHUB_TOKEN=... bin/check-target-branch --repository=doctrine/orm --event-path=event.json
```

`GITHUB_TOKEN` is read from the environment. The remaining options default to
the environment variables available inside a GitHub Actions run.

| Option | Default |
| --- | --- |
| `--repository` | `GITHUB_REPOSITORY` |
| `--event-path` | `GITHUB_EVENT_PATH` |
| `--api-url` | `GITHUB_API_URL` or `https://api.github.com` |
| `--labels-config` | `config/labels.json` |
| `--patch-labels` | `PATCH_LABELS` |
| `--minor-labels` | `MINOR_LABELS` |
| `--major-labels` | `MAJOR_LABELS` |
| `--require-type-label` | `REQUIRE_TYPE_LABEL=true` |
| `--comment-author` | `COMMENT_AUTHOR` or `github-actions[bot]` |
| `--fail-on-mismatch` | `FAIL_ON_MISMATCH=true` |

## Labels

`config/labels.json` is the shared source of truth for labels. It has two parts:

- `labels` — every canonical label, including ones unrelated to branch targeting.
- `targets` — the subset of labels that map to the `patch`, `minor`, and `major`
  branch tiers.

Workflows use it at the same commit as the automation, so other actions can
reuse the same definitions. Repositories with different label names can override
individual target lists through the command options or workflow inputs.
