<p align="center">
    <a href="https://packagist.org/packages/offload-project/laravel-toggle"><img src="https://img.shields.io/packagist/v/offload-project/laravel-toggle.svg?style=flat-square" alt="Latest Version on Packagist"></a>
    <a href="https://github.com/offload-project/laravel-toggle/actions"><img src="https://img.shields.io/github/actions/workflow/status/offload-project/laravel-toggle/tests.yml?branch=main&style=flat-square" alt="GitHub Tests Action Status"></a>
    <a href="https://packagist.org/packages/offload-project/laravel-toggle"><img src="https://img.shields.io/packagist/dt/offload-project/laravel-toggle.svg?style=flat-square" alt="Total Downloads"></a>
</p>

# Laravel Toggle

A simple and flexible feature toggle (feature flag) package for Laravel. Control feature rollouts with config-based
flags, database-driven toggles, or both.

## Features

- **Two storage drivers**: Config (environment variables) or Database (runtime toggleable)
- **Per-flag driver routing**: Mix config and database flags in the same app via `database_flags`
- **Layered approach**: Database driver falls back to config, allowing gradual migration
- **Built-in caching**: Configurable cache store and TTL for performance
- **Blade directives**: `@toggle`, `@elsetoggle`, `@endtoggle` for clean templates
- **Enum support**: Use backed enums for type-safe toggle names
- **Artisan commands**: Scaffold new toggles and manage cache
- **Configurable defaults**: Return false, true, or throw exceptions for undefined toggles

## Why Laravel Toggle?

Laravel's first-party [Pennant](https://github.com/laravel/pennant) package is designed for user-segmented rollouts and
A/B testing. Laravel Toggle is simpler — it's for global on/off switches controlled by environment variables or database
records, with no user resolution or driver complexity.

## Requirements

- PHP 8.2+
- Laravel 11 or 12 or 13

## Installation

```bash
composer require offload-project/laravel-toggle
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag=toggle-config
```

If using the database driver, publish and run the migrations:

```bash
php artisan vendor:publish --tag=toggle-migrations
php artisan migrate
```

## Quick Start

### Define a toggle in config

Add your feature flags to `config/toggle.php`:

```php
'flags' => [
    'new-checkout' => env('TOGGLE_NEW_CHECKOUT', false),
    'dark-mode' => env('TOGGLE_DARK_MODE', true),
],
```

### Check toggles in code

```php
use OffloadProject\Toggle\Facades\Toggle;

if (Toggle::active('new-checkout')) {
    // New checkout flow
}

if (Toggle::inactive('dark-mode')) {
    // Light mode only
}
```

### Use Blade directives

```blade
@toggle('new-checkout')
    <x-new-checkout-form />
@elsetoggle
    <x-legacy-checkout-form />
@endtoggle
```

## Configuration

### Driver

Set the driver in your `.env` file:

```env
TOGGLE_DRIVER=config   # Read-only, uses config/toggle.php flags
TOGGLE_DRIVER=database # Read-write, falls back to config
```

The **config driver** is read-only at runtime - values come from environment variables and config files.

The **database driver** checks the database first, then falls back to config values. This allows you to define defaults
in config while overriding specific toggles at runtime.

### Per-flag driver routing

You can mix both drivers in the same application. Define config-driven flags in `flags` and list database-driven flags
in `database_flags`:

```php
// config/toggle.php

'flags' => [
    // Config-driven, read-only — controlled by .env
    'new-checkout' => env('TOGGLE_NEW_CHECKOUT', false),
    'dark-mode' => env('TOGGLE_DARK_MODE', true),
],

'database_flags' => [
    // Database-driven, mutable at runtime via Toggle::enable() / Toggle::disable()
    'maintenance-banner',
    'beta-access',
],
```

Resolution logic:
- Flags in `database_flags` always use the database driver (with config fallback)
- Flags in `flags` always use the config driver (read-only)
- Unlisted flags use the global `driver` setting

This lets you keep stable flags in `.env` while allowing runtime control over flags that need to change without a deploy.

### Default behavior for undefined toggles

```env
TOGGLE_DEFAULT=false     # Return false (default)
TOGGLE_DEFAULT=true      # Return true
TOGGLE_DEFAULT=exception # Throw ToggleNotFoundException
```

### Caching

```env
TOGGLE_CACHE_ENABLED=true
TOGGLE_CACHE_STORE=redis  # null uses default cache store
TOGGLE_CACHE_TTL=3600     # seconds
```

## Usage

### Facade methods

```php
use OffloadProject\Toggle\Facades\Toggle;

// Check if active
Toggle::active('feature-name');    // bool
Toggle::inactive('feature-name');  // bool

// Modify toggles (database driver only)
Toggle::enable('feature-name');   // Enable a toggle
Toggle::disable('feature-name');  // Disable a toggle
Toggle::delete('feature-name');     // Remove from database

// Get all toggles
Toggle::all(); // ['feature-name' => true, ...]

// Cache management
Toggle::forgetCache('feature-name'); // Clear specific toggle
Toggle::flushCache();                // Clear all toggles
```

### Using enums

Define your toggles as a backed enum for type safety:

```php
enum Feature: string
{
    case NewCheckout = 'new-checkout';
    case DarkMode = 'dark-mode';
    case BetaFeatures = 'beta-features';
}
```

Use the enum directly:

```php
use App\Enums\Feature;

if (Toggle::active(Feature::NewCheckout)) {
    // ...
}

Toggle::enable(Feature::BetaFeatures);
```

### Eloquent model

When using the database driver, you can also use the `Toggle` model directly:

```php
use OffloadProject\Toggle\Models\Toggle;

// Query toggles
$toggle = Toggle::where('name', 'new-checkout')->first();

// Create or update
Toggle::updateOrCreate(
    ['name' => 'new-checkout'],
    ['active' => true]
);
```

The model automatically clears the cache when toggles are saved or deleted.

### Inertia

Share all toggles with your frontend by using the provided Inertia middleware. Replace your `HandleInertiaRequests`
middleware in `bootstrap/app.php`:

```php
use OffloadProject\Toggle\Middleware\ShareTogglesWithInertia;

->withMiddleware(function (Middleware $middleware) {
    $middleware->web(append: [
        ShareTogglesWithInertia::class,
    ]);
})
```

All toggles will be available as the `flags` prop in your frontend:

```js
// Vue/React
const {flags} = usePage().props

if (flags.newCheckout) {
    // Show new checkout
}
```

## Artisan Commands

### List all toggles

```bash
php artisan toggle:list
```

Displays a table of all defined toggles and their current state.

### Create a toggle

```bash
# Create a config-based toggle
php artisan toggle:create new-feature

# Create as active by default
php artisan toggle:create new-feature --active

# Also create a database record
php artisan toggle:create new-feature --active --db
```

This command will:

- Add the flag to `config/toggle.php`
- Add the environment variable to `.env`
- Optionally create a database record

### Clear cache

```bash
# Clear all toggle caches
php artisan toggle:cache-clear

# Clear a specific toggle
php artisan toggle:cache-clear new-feature
```

## Testing

```bash
composer test
```

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
