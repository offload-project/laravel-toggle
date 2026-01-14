<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use OffloadProject\Toggle\Facades\Toggle;

beforeEach(function () {
    config(['toggle.cache.enabled' => true]);
    config(['toggle.cache.prefix' => 'toggle:']);
    Cache::flush();
});

it('caches toggle values', function () {
    // First call - should hit the driver
    Toggle::active('test-flag');

    // Value should be cached
    expect(Cache::has('toggle:test-flag'))->toBeTrue();
});

it('can clear cache for specific toggle', function () {
    Toggle::active('test-flag');
    expect(Cache::has('toggle:test-flag'))->toBeTrue();

    Toggle::forgetCache('test-flag');
    expect(Cache::has('toggle:test-flag'))->toBeFalse();
});

it('can flush all toggle caches', function () {
    Toggle::active('test-flag');
    Toggle::active('disabled-flag');

    expect(Cache::has('toggle:test-flag'))->toBeTrue();
    expect(Cache::has('toggle:disabled-flag'))->toBeTrue();

    Toggle::flushCache();

    expect(Cache::has('toggle:test-flag'))->toBeFalse();
    expect(Cache::has('toggle:disabled-flag'))->toBeFalse();
});

it('gracefully handles cache unavailability during active check', function () {
    // Mock cache to throw an exception (simulating database not existing)
    Cache::shouldReceive('store')
        ->andThrow(new RuntimeException('Database does not exist'));

    // Should still resolve the toggle without cache
    expect(Toggle::active('test-flag'))->toBeTrue();
    expect(Toggle::active('disabled-flag'))->toBeFalse();
});

it('gracefully handles cache unavailability during forgetCache', function () {
    Cache::shouldReceive('store')
        ->andThrow(new RuntimeException('Database does not exist'));

    // Should return false but not throw
    expect(Toggle::forgetCache('test-flag'))->toBeFalse();
});

it('gracefully handles cache unavailability during flushCache', function () {
    Cache::shouldReceive('store')
        ->andThrow(new RuntimeException('Database does not exist'));

    // Should return false but not throw
    expect(Toggle::flushCache())->toBeFalse();
});
