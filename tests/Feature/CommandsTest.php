<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use OffloadProject\Toggle\Facades\Toggle;

describe('toggle:cache-clear command', function () {
    beforeEach(function () {
        config(['toggle.cache.enabled' => true]);
        Cache::flush();
    });

    it('clears all toggle caches', function () {
        Toggle::active('test-flag');
        Toggle::active('disabled-flag');

        $this->artisan('toggle:cache-clear')
            ->assertSuccessful();

        expect(Cache::has('toggle:test-flag'))->toBeFalse()
            ->and(Cache::has('toggle:disabled-flag'))->toBeFalse();
    });

    it('clears specific toggle cache', function () {
        Toggle::active('test-flag');
        Toggle::active('disabled-flag');

        $this->artisan('toggle:cache-clear', ['name' => 'test-flag'])
            ->assertSuccessful();

        expect(Cache::has('toggle:test-flag'))->toBeFalse()
            ->and(Cache::has('toggle:disabled-flag'))->toBeTrue();
    });
});
