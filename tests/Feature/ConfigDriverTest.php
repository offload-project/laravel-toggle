<?php

declare(strict_types=1);

use OffloadProject\Toggle\Drivers\ConfigDriver;
use OffloadProject\Toggle\Exceptions\ReadOnlyDriverException;

it('gets toggle value from config', function () {
    $driver = app(ConfigDriver::class);

    expect($driver->get('test-flag'))->toBeTrue()
        ->and($driver->get('disabled-flag'))->toBeFalse();
});

it('returns null for undefined toggles', function () {
    $driver = app(ConfigDriver::class);

    expect($driver->get('undefined-flag'))->toBeNull();
});

it('checks if toggle exists', function () {
    $driver = app(ConfigDriver::class);

    expect($driver->has('test-flag'))->toBeTrue()
        ->and($driver->has('undefined-flag'))->toBeFalse();
});

it('returns all toggles', function () {
    $driver = app(ConfigDriver::class);
    $all = $driver->all();

    expect($all)->toHaveKey('test-flag')
        ->and($all['test-flag'])->toBeTrue();
});

it('throws exception on set operations', function () {
    $driver = app(ConfigDriver::class);

    $driver->set('test-flag', false);
})->throws(ReadOnlyDriverException::class, 'config driver is read-only');

it('throws exception on delete operations', function () {
    $driver = app(ConfigDriver::class);

    $driver->delete('test-flag');
})->throws(ReadOnlyDriverException::class, 'config driver is read-only');
