<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use OffloadProject\Toggle\Exceptions\ReadOnlyDriverException;
use OffloadProject\Toggle\ToggleManager;

beforeEach(function () {
    config([
        'toggle.driver' => 'config',
        'toggle.flags' => [
            'config-flag' => true,
            'shared-flag' => false,
        ],
        'toggle.database_flags' => [
            'db-flag',
            'shared-flag',
        ],
    ]);

    // Rebind the toggle manager so the PerFlagDriver is created
    app()->forgetInstance(ToggleManager::class);
    app()->forgetInstance('toggle');
});

it('resolves database flags from the database', function () {
    DB::table('toggles')->insert([
        'name' => 'db-flag',
        'active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $manager = app(ToggleManager::class);

    expect($manager->active('db-flag'))->toBeTrue();
});

it('resolves config flags from config', function () {
    $manager = app(ToggleManager::class);

    expect($manager->active('config-flag'))->toBeTrue();
});

it('config flags remain read-only even with database_flags configured', function () {
    $manager = app(ToggleManager::class);

    $manager->enable('config-flag');
})->throws(ReadOnlyDriverException::class);

it('database flags can be set at runtime', function () {
    $manager = app(ToggleManager::class);

    expect($manager->enable('db-flag'))->toBeTrue();
    expect(DB::table('toggles')->where('name', 'db-flag')->value('active'))->toBe(1);
});

it('database flags fall back to config when not in database', function () {
    // shared-flag is in both arrays, has value false in config, not in DB
    $manager = app(ToggleManager::class);

    expect($manager->active('shared-flag'))->toBeFalse();
});

it('database flags take precedence over config when present in database', function () {
    // shared-flag is false in config but true in DB
    DB::table('toggles')->insert([
        'name' => 'shared-flag',
        'active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $manager = app(ToggleManager::class);

    expect($manager->active('shared-flag'))->toBeTrue();
});

it('unlisted flags use the global default driver', function () {
    // Global driver is 'config', so unlisted flags resolve via config
    config(['toggle.flags' => [
        'config-flag' => true,
        'shared-flag' => false,
        'unlisted-via-config' => true,
    ]]);

    app()->forgetInstance(ToggleManager::class);
    app()->forgetInstance('toggle');

    $manager = app(ToggleManager::class);

    expect($manager->active('unlisted-via-config'))->toBeTrue();
});

it('all() merges results from both drivers', function () {
    DB::table('toggles')->insert([
        'name' => 'db-flag',
        'active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $manager = app(ToggleManager::class);
    $all = $manager->all();

    expect($all)->toHaveKey('config-flag')
        ->and($all['config-flag'])->toBeTrue()
        ->and($all)->toHaveKey('db-flag')
        ->and($all['db-flag'])->toBeTrue();
});

it('logs warning when flags overlap between config and database_flags', function () {
    config(['app.debug' => true]);

    Log::shouldReceive('warning')
        ->once()
        ->withArgs(fn (string $message) => str_contains($message, 'shared-flag'));

    // Trigger boot on the existing provider instance
    app()->forgetInstance(ToggleManager::class);
    app()->forgetInstance('toggle');

    /** @var OffloadProject\Toggle\ToggleServiceProvider $provider */
    $provider = app()->getProvider(OffloadProject\Toggle\ToggleServiceProvider::class);
    $provider->boot();
});
