# Security Policy

Please **do not report security vulnerabilities through public GitHub issues, pull requests or discussions.** A public report exposes every user of the library before a fix is available.

## Reporting a vulnerability

**Preferred: GitHub private vulnerability reporting**

1. Open the **Security** tab of the repository the issue affects.
2. Click **Report a vulnerability** (on the Security tab, or at the top of its Security policy page).
3. Describe the problem and submit.

Your report is visible only to the maintainers. It lets us discuss the issue with you, prepare a fix in private and publish a security advisory once patched releases are available.

If you don't see a **Report a vulnerability** button, private reporting is not enabled for that repository. Use the email address below instead.

**Fallback: email**

Only if you cannot use GitHub, send your report to security@doctrine-project.org. Emails sent to this address are forwarded to the Doctrine core team's private mailing list.

Whichever way you report, the following details help us a lot:

- the name and affected version(s) of the library, and of PHP and any relevant dependencies
- a description of the problem and its impact
- a minimal way to reproduce it
- a suggested fix, if you have one

## What is a security issue?

A security issue is a flaw in the library itself that lets an attacker compromise the confidentiality, integrity or availability of an application, **even though that application uses the library as documented**. Examples:

- **Injection:** user-controlled input is interpreted as part of a query or command where the library should treat it as a plain value.
- **Silently weakened protection:** a bug that causes a security-related configuration (such as encryption settings) to be dropped or ignored, so that data is stored or transmitted unprotected.
- **Unsafe instantiation or code execution:** the library instantiates arbitrary classes, or generated code can be made to include attacker-controlled code, based on data from the database or other untrusted input.
- **Unintended data access:** the library reads, returns or writes data the application did not ask for, for example because a filter is dropped or a reference resolves to the wrong target.
- **Denial of service:** small, crafted input causes disproportionate resource consumption inside the library.

The following are usually **not** security issues:

- Ordinary bugs with no security consequence. Please use the repository's issue tracker for these.
- An application passing unvalidated input to an API that is documented to accept raw, unescaped expressions.
- Vulnerabilities in PHP, database servers, drivers or other dependencies. Please report those to the respective projects.
- Insecure deployment, such as missing authentication or an exposed port.

**If you are unsure, report privately.** We would much rather triage a report that turns out to be a regular bug than have a vulnerability discussed in public. If we conclude that it is not a vulnerability, we will tell you, and you are then welcome to open a public issue.

## Reported publicly by mistake?

If you have already opened a public issue or pull request for something that may be a vulnerability, please report it privately as described above and reference it. We may edit, hide or lock the public item and continue in private. Please do not add further exploit details in the meantime.

## What happens next

We follow the Doctrine security policy: we confirm the issue, acknowledge your report, prepare a patch, release fixed versions and publish an advisory crediting you, unless you prefer to remain anonymous. Please do not disclose the issue publicly until we have published the fix.

## Repository-specific policies

A repository that has its own `SECURITY.md` follows that file instead of this default.
