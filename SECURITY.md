# Security Policy

## Supported versions

Security fixes are applied to the latest minor release line. Older minor versions may receive fixes for critical issues at the maintainers' discretion — when in doubt, please upgrade.

| Version       | Supported              |
| ------------- | ---------------------- |
| `2.x`         | ✅                     |
| `1.x`         | ⚠️ critical fixes only |
| `< 1.0`       | ❌ (please upgrade)    |

## Reporting a vulnerability

**Please do not open a public GitHub issue for security reports.**

Use [GitHub Security Advisories](https://github.com/offload-project/laravel-toggle/security/advisories/new) to report privately. This lets us discuss, fix, and coordinate disclosure before details become public.

When reporting, please include:

- A description of the issue and its potential impact.
- Steps to reproduce, or a minimal proof-of-concept.
- Affected package version(s), Laravel version, and PHP version.
- Any suggested fix or mitigation (optional).

## Response expectations

- **Acknowledgement:** within 5 business days.
- **Initial assessment:** within 10 business days.
- **Fix timeline:** depends on severity. Critical issues get prioritized; lower-severity issues may be batched into the next regular release.

We'll keep you updated on progress and credit you in the advisory unless you'd prefer to stay anonymous.

## Scope

Things in scope for this project:

- Vulnerabilities in any code published under `OffloadProject\Toggle\` (drivers, manager, models, middleware, facade, console commands).
- Toggle resolution issues — bypasses that let an inactive toggle resolve as active (or vice versa) due to caching, fallback, or per-flag routing logic.
- Cache poisoning or stale-cache issues that could keep a flag in the wrong state after `enable()`/`disable()`/`delete()` calls.
- Mass-assignment, SQL injection, or query-scope bypass in the `Toggle` Eloquent model or database driver.
- Information disclosure via the Inertia middleware (e.g., leaking flags that should not be shared with the frontend).
- Insecure defaults in the published config or migrations.

Things **not** in scope (please report upstream or with the relevant project):

- Vulnerabilities in Laravel itself or other Composer dependencies — please file with the respective project.
- Application-level misconfiguration (e.g., exposing a sensitive flag through the Inertia middleware in a public route).
- Issues caused by user-supplied custom drivers or overridden manager methods.
- Vulnerabilities in the host application's cache driver, database, or queue.

## Disclosure

Once a fix is published, we will:

1. Publish a GitHub Security Advisory with details and credit.
2. Tag a patch release.
3. Update the changelog with a brief mention (without exploit details prior to the disclosure window).

Thanks for helping keep the project and its users safe.
