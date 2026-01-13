<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use OffloadProject\Toggle\Drivers\DatabaseDriver;

beforeEach(function () {
    config(['toggle.driver' => 'database']);
});

it('gets toggle from database', function () {
    DB::table('toggles')->insert([
        'name' => 'db-flag',
        'active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $driver = app(DatabaseDriver::class);

    expect($driver->get('db-flag'))->toBeTrue();
});

it('falls back to config when not in database', function () {
    $driver = app(DatabaseDriver::class);

    expect($driver->get('test-flag'))->toBeTrue();
});

it('database takes precedence over config', function () {
    // Config has test-flag as true
    DB::table('toggles')->insert([
        'name' => 'test-flag',
        'active' => false,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $driver = app(DatabaseDriver::class);

    expect($driver->get('test-flag'))->toBeFalse();
});

it('can set toggle in database', function () {
    $driver = app(DatabaseDriver::class);

    expect($driver->set('new-flag', true))->toBeTrue();

    expect(DB::table('toggles')->where('name', 'new-flag')->value('active'))->toBe(1);
});

it('can update existing toggle', function () {
    DB::table('toggles')->insert([
        'name' => 'existing-flag',
        'active' => false,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $driver = app(DatabaseDriver::class);
    $driver->set('existing-flag', true);

    expect(DB::table('toggles')->where('name', 'existing-flag')->value('active'))->toBe(1);
});

it('can delete toggle from database', function () {
    DB::table('toggles')->insert([
        'name' => 'deletable-flag',
        'active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $driver = app(DatabaseDriver::class);

    expect($driver->delete('deletable-flag'))->toBeTrue();
    expect(DB::table('toggles')->where('name', 'deletable-flag')->exists())->toBeFalse();
});

it('merges database and config flags in all()', function () {
    DB::table('toggles')->insert([
        'name' => 'db-only-flag',
        'active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $driver = app(DatabaseDriver::class);
    $all = $driver->all();

    expect($all)->toHaveKey('test-flag')
        ->and($all)->toHaveKey('db-only-flag');
});
