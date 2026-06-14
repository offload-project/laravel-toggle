# Contributing to Laravel Toggle

Thanks for your interest in contributing! This document outlines the process and standards for contributing to `offload-project/laravel-toggle`.

## Code of Conduct

By participating in this project, you agree to treat fellow contributors with respect. Be kind, assume good intent, and keep discussions focused on the work. See [CODE_OF_CONDUCT.md](CODE_OF_CONDUCT.md).

## Ways to Contribute

- Reporting bugs via the [Bug Report](.github/ISSUE_TEMPLATE/bug_report.md) template
- Proposing new features via the [Feature Request](.github/ISSUE_TEMPLATE/feature_request.md) template
- Improving documentation (`README.md`, `CHANGELOG.md`)
- Fixing bugs or implementing features through pull requests
- Reviewing open pull requests

Before opening a large PR, please open an issue first to discuss the approach.

## Requirements

- PHP **8.3+** (CI matrix runs 8.3, 8.4, 8.5)
- Composer 2
- A local SQLite extension (the test suite uses an in-memory SQLite database via Orchestra Testbench)

## Getting Set Up

1. Fork the repository on GitHub and clone your fork:

   ```bash
   git clone git@github.com:<your-username>/laravel-toggle.git
   cd laravel-toggle
   ```

2. Install dependencies:

   ```bash
   composer install
   ```

3. Install the Git hooks (runs Pint pre-commit, validates Conventional Commits on commit-msg, runs tests and static analysis pre-push):

   ```bash
   composer install-hooks
   ```

4. Create a feature branch off `main`:

   ```bash
   git checkout -b feat/short-description
   ```

## Development Workflow

This package supports Laravel 11, 12, and 13 and PHP 8.3–8.5. Changes must work across that matrix.

### Running the Test Suite

```bash
composer test
```

Run with coverage:

```bash
composer test-coverage
```

Tests are written with [Pest](https://pestphp.com/) and live under `tests/`. New behavior should be covered by tests; bug fixes should include a regression test.

### Static Analysis

```bash
composer analyse
```

We use Larastan (PHPStan for Laravel). If you must suppress a finding, prefer narrow ignores via the baseline over loosening the rule set, and explain why in your PR.

### Code Style

```bash
composer pint
```

Pint runs on `pre-commit`. PRs must be Pint-clean — the `code-style.yml` workflow will fail otherwise.

## Commit Messages

We use [Conventional Commits](https://www.conventionalcommits.org/). The `commit-msg` hook validates this; CI/release tooling depends on it.

Format: `<type>(<optional scope>): <description>`

Common types used in this repo:

| Type         | Use for                                                             |
| ------------ | ------------------------------------------------------------------- |
| `feat`       | New user-facing functionality                                       |
| `fix`        | Bug fixes                                                           |
| `deprecate`  | Marking existing API as deprecated                                  |
| `refactor`   | Internal change with no behavior difference                         |
| `test`       | Adding or updating tests                                            |
| `docs`       | Documentation only                                                  |
| `chore`      | Tooling, dependency bumps, repo housekeeping                        |
| `ci`         | Changes to GitHub Actions workflows                                 |

Breaking changes: add `!` after the type (e.g., `feat!: change Toggle::all() return type`) and explain the migration path in the PR body.

## Pull Requests

1. Make sure your branch is up to date with `main`.
2. Run the full local check before pushing:

   ```bash
   composer pint && composer analyse && composer test
   ```

3. Push your branch and open a PR against `main` using the [PR template](.github/pull_request_template.md).
4. Fill in:
    - What changed and why
    - Type of change (bug fix, feature, breaking, deprecation, etc.)
    - How it was tested (PHP/Laravel/DB versions)
    - Whether docs or `CHANGELOG.md` were updated
5. Keep PRs focused. One logical change per PR makes review faster and bisection easier.
6. CI must pass before review:
    - `tests.yml` — Pest across the PHP × Laravel × stability matrix
    - `code-style.yml` — Pint
7. Address review feedback in additional commits rather than force-pushing while review is active.

## Adding or Changing Features

When working on this package, keep these areas in mind:

- **Drivers** — `Driver` is the contract for `ConfigDriver` and `DatabaseDriver`. New drivers should implement it and be wired through `ToggleManager`.
- **Per-flag routing** — `flags` and `database_flags` keep config-driven and database-driven flags separate. Changes to resolution order are user-visible.
- **Cache** — `enable()`, `disable()`, `delete()`, and the model's saved/deleted hooks must keep the cache coherent. Don't add code paths that mutate state without clearing the cached value.
- **Blade directives & middleware** — `@toggle` / `@elsetoggle` / `@endtoggle` and `ShareTogglesWithInertia` are part of the public API.
- **Config** — new config keys must have safe defaults and be documented in `config/toggle.php` with a comment.
- **Migrations** — schema changes need a new migration. Don't edit existing published migrations.
- **Exceptions** — `ToggleNotFoundException` is the established pattern; follow it for new error types.

## Documentation

If your change affects public API, configuration, or usage, update:

- `README.md` — quick start / feature list
- `CHANGELOG.md` — under the `Unreleased` section (or note in your PR if you'd like a maintainer to add it)

## Reporting Security Issues

Please do **not** open a public issue for security vulnerabilities. Report them privately via GitHub's "Report a vulnerability" feature on the repository's Security tab so a fix can be coordinated before disclosure. See [SECURITY.md](SECURITY.md).

## License

By contributing, you agree that your contributions will be licensed under the [MIT License](LICENSE.md) that covers this project.
