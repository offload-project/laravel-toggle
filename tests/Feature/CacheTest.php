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
