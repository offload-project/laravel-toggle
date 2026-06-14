---
name: Laravel Toggle
description: Conventions and APIs for the offload-project/laravel-toggle package — config and database feature toggles, per-flag driver routing, Blade directives, Inertia sharing, and cache management.
compatible_agents:
  - Claude Code
  - Cursor
tags:
  - laravel
  - php
  - feature-flags
  - feature-toggles
  - inertia
  - blade
  - eloquent
---

## Context

`offload-project/laravel-toggle` is a Laravel 11/12/13 package (PHP 8.3+) for global on/off feature flags. It ships:

- A `ToggleManager` resolved via the `Toggle` facade (`OffloadProject\Toggle\Facades\Toggle`).
- A `Driver` contract with three implementations: `ConfigDriver` (read-only, env-driven), `DatabaseDriver` (mutable at runtime with config fallback), and `PerFlagDriver` (routes each flag to the config or database driver based on `flags` / `database_flags` lists).
- A `Toggle` Eloquent model (`OffloadProject\Toggle\Models\Toggle`) backing the database driver.
- Blade directives `@toggle`, `@elsetoggle`, `@endtoggle`.
- `ShareTogglesWithInertia` middleware that exposes all flags to the frontend as a `flags` Inertia prop (keys camelCased).
- Artisan commands `toggle:list`, `toggle:create`, `toggle:cache-clear`.
- Exceptions `ToggleNotFoundException` and `ReadOnlyDriverException`.

Apply this skill when working in a Laravel app that has `offload-project/laravel-toggle` in `composer.json`, or when the user asks for help with `Toggle::`, `@toggle`, the `Toggle` model, feature flags, or driver/cache wiring in this package.

## Rules

### Calling toggles

1. Always check toggle state through the `Toggle` facade (`Toggle::active($name)` / `Toggle::inactive($name)`). Do **not** read `config('toggle.flags.foo')` or query the `toggles` table directly — those paths bypass per-flag routing and the cache.
2. Pass either a string key (kebab-case) or a backed-string enum case. Prefer enums in application code for type safety; reserve raw strings for Blade or quick scripts.
3. Use `Toggle::inactive($name)` instead of `! Toggle::active($name)` so intent is explicit in diffs.

### Enabling / disabling at runtime

4. `Toggle::enable($name)`, `Toggle::disable($name)`, and `Toggle::delete($name)` only work when the flag resolves through the database driver. If you call them on a config-only flag, the underlying `ConfigDriver` throws `ReadOnlyDriverException`. To make a flag mutable, list it in `database_flags` (or set the global driver to `database`).
5. `enable()` / `disable()` / `delete()` automatically forget the cache entry for that flag. Do **not** add manual `Toggle::forgetCache()` calls after them.
6. Modifying the `Toggle` Eloquent model directly (e.g. `Toggle::updateOrCreate(...)`) also clears the cache because the model's `saved` and `deleted` hooks call `Toggle::forgetCache()`. Prefer the facade methods, but the model is safe when you need to seed or bulk-write.

### Per-flag driver routing

7. Put **read-only / env-driven** flags in `config('toggle.flags')` with an `env()` default. These are baked into the deploy.
8. Put **runtime-mutable** flag names in `config('toggle.database_flags')`. Listed flags always resolve through the database driver (with config fallback), regardless of `TOGGLE_DRIVER`.
9. Don't list the same flag in both `flags` and `database_flags` unless you intend the config value to be the fallback when the database row is missing — `database_flags` wins for resolution, and `flags` only contributes the fallback value.
10. For unlisted flags, the global `TOGGLE_DRIVER` (`config` or `database`) decides which driver handles them. Avoid relying on unlisted flags in production code; declare every flag explicitly.

### Defaults for unknown flags

11. Set `TOGGLE_DEFAULT=false` (default), `true`, or `exception`. Use `exception` in development to catch typos early; keep production at `false` (or `true` for safe-by-default flags).
12. When `TOGGLE_DEFAULT=exception`, callers must be prepared for `ToggleNotFoundException`. Don't wrap every `Toggle::active()` in try/catch — instead, declare the flag.

### Caching

13. Caching is on by default (`TOGGLE_CACHE_ENABLED=true`) with a 1-hour TTL. The cache key prefix is `toggle:`. Leave caching on in production.
14. If the cache store is unavailable, `Toggle::active()` still resolves correctly by falling through to the driver — there's no need to wrap calls in defensive try/catch.
15. After bulk-changing many flags out-of-band (e.g. a seed or migration), call `Toggle::flushCache()` once rather than `forgetCache()` per flag.
16. Tests that exercise toggle changes should either use the `array` cache driver or call `Toggle::flushCache()` in `beforeEach` to avoid cross-test bleed.

### Blade directives

17. Use `@toggle('flag-name') ... @elsetoggle ... @endtoggle` for template-side conditionals. The directive accepts a string or an enum case (passed via `{{ Feature::NewCheckout->value }}` or directly if your Blade compiler supports it).
18. Do **not** call `Toggle::active()` inline in Blade unless you need to combine it with another expression — `@toggle` is the canonical form.

### Inertia integration

19. To expose flags to the frontend, replace `HandleInertiaRequests` in `bootstrap/app.php` with `ShareTogglesWithInertia` (or extend it). The middleware shares all flags as a `flags` prop with camelCased keys (`new-checkout` → `newCheckout`).
20. Anything exposed via the Inertia middleware is **public to the browser**. Do not put sensitive kill-switches (rate-limit bypasses, internal admin features) in the same toggle namespace as user-facing flags; route those through a separate authorization check.

### Artisan

21. Use `php artisan toggle:create <name>` to scaffold a new config-driven flag. Add `--active` to default it to true, `--db` to also create a database row. The command edits `config/toggle.php` and `.env`; review the diff before committing.
22. `php artisan toggle:list` shows all defined flags and current state — useful for verifying a deploy or debugging which driver a flag is resolving through.
23. `php artisan toggle:cache-clear [name]` clears the cache for a specific flag or all flags.

### Naming

24. Use kebab-case for flag names (`new-checkout`, `dark-mode`). The Inertia middleware converts these to camelCase for the frontend; mixing snake_case or PascalCase breaks that contract.
25. Prefix `TOGGLE_` env vars and shout-case the rest (`TOGGLE_NEW_CHECKOUT`). The `toggle:create` command follows this convention; match it for hand-added flags.

## Examples

### Defining flags in config

```php
// config/toggle.php

'flags' => [
    // Config-driven, read-only — controlled by .env
    'new-checkout' => env('TOGGLE_NEW_CHECKOUT', false),
    'dark-mode'    => env('TOGGLE_DARK_MODE', true),
],

'database_flags' => [
    // Database-driven, mutable at runtime via Toggle::enable() / Toggle::disable()
    'maintenance-banner',
    'beta-access',
],
```

### Checking a flag

```php
use OffloadProject\Toggle\Facades\Toggle;

if (Toggle::active('new-checkout')) {
    return new NewCheckoutController()->show($request);
}

if (Toggle::inactive('dark-mode')) {
    // Light mode only
}
```

### Using a backed enum

```php
enum Feature: string
{
    case NewCheckout = 'new-checkout';
    case DarkMode    = 'dark-mode';
    case BetaAccess  = 'beta-access';
}

use App\Enums\Feature;
use OffloadProject\Toggle\Facades\Toggle;

if (Toggle::active(Feature::NewCheckout)) {
    // ...
}

Toggle::enable(Feature::BetaAccess);
```

### Blade

```blade
@toggle('new-checkout')
    <x-new-checkout-form />
@elsetoggle
    <x-legacy-checkout-form />
@endtoggle
```

### Mutating at runtime (database-routed flag)

```php
use OffloadProject\Toggle\Facades\Toggle;

// 'maintenance-banner' is listed in config('toggle.database_flags')
Toggle::enable('maintenance-banner');
Toggle::disable('maintenance-banner');
Toggle::delete('maintenance-banner'); // removes the row, falls back to config
```

### Sharing flags with Inertia

```php
// bootstrap/app.php
use OffloadProject\Toggle\Middleware\ShareTogglesWithInertia;

->withMiddleware(function (Middleware $middleware) {
    $middleware->web(append: [
        ShareTogglesWithInertia::class,
    ]);
})
```

```js
// resources/js/Pages/Checkout.vue (or React equivalent)
const {flags} = usePage().props

if (flags.newCheckout) {
    // ...
}
```

### Testing pattern

```php
use OffloadProject\Toggle\Facades\Toggle;

beforeEach(function () {
    config()->set('toggle.driver', 'database');
    Toggle::flushCache();
});

it('shows the new checkout when enabled', function () {
    Toggle::enable('new-checkout');

    $this->get('/checkout')->assertSee('New Checkout');
});
```

## Anti-patterns

- ❌ Reading `config('toggle.flags.foo')` directly — bypasses per-flag routing and the cache; database overrides will be ignored.
- ❌ Querying the `toggles` table with raw Eloquent in feature code — use the facade so the cache and config fallback stay consistent.
- ❌ Calling `Toggle::enable()` / `disable()` / `delete()` on a flag that's only in `config('toggle.flags')` — `ReadOnlyDriverException` will be thrown. Add the flag to `database_flags` (or switch `TOGGLE_DRIVER` to `database`).
- ❌ Wrapping every `Toggle::active()` in try/catch. With `TOGGLE_DEFAULT` set to `false` or `true`, undefined flags don't throw; with `exception` only undefined flags throw and the right fix is to declare the flag.
- ❌ Manually clearing the cache after `Toggle::enable()` / `disable()` — those facade methods already do it.
- ❌ Putting sensitive admin kill-switches in the same flag namespace as user-facing flags when `ShareTogglesWithInertia` is enabled — they will leak to the browser.
- ❌ Subclassing `OffloadProject\Toggle\Models\Toggle` to add behavior. Use events on the model or a separate service; the model is part of the driver wiring.
- ❌ Mixing snake_case / camelCase flag names. The Inertia middleware camelCases kebab-case names — anything else produces inconsistent prop keys.
- ❌ Editing files inside `vendor/offload-project/laravel-toggle`. All extension points (driver, cache store, default behavior, flags) are exposed via `config/toggle.php` and the `Driver` contract.

## References

- Repository: <https://github.com/offload-project/laravel-toggle>
- README: <https://github.com/offload-project/laravel-toggle/blob/main/README.md>
- Pennant comparison (when to prefer Pennant): <https://github.com/laravel/pennant>
