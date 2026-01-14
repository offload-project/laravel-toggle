<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use OffloadProject\Toggle\Middleware\ShareTogglesWithInertia;

it('shares toggles as flags prop', function () {
    $middleware = new ShareTogglesWithInertia;
    $request = Request::create('/');

    $shared = $middleware->share($request);

    expect($shared)->toHaveKey('flags')
        ->and($shared['flags'])->toBeArray();
});

it('converts kebab-case toggle names to camelCase', function () {
    config()->set('toggle.flags', [
        'test-flag' => true,
        'another-feature-flag' => false,
        'simple' => true,
    ]);

    $middleware = new ShareTogglesWithInertia;
    $request = Request::create('/');

    $shared = $middleware->share($request);

    expect($shared['flags'])
        ->toHaveKey('testFlag')
        ->toHaveKey('anotherFeatureFlag')
        ->toHaveKey('simple')
        ->not->toHaveKey('test-flag')
        ->not->toHaveKey('another-feature-flag');
});

it('preserves toggle values after key conversion', function () {
    config()->set('toggle.flags', [
        'enabled-flag' => true,
        'disabled-flag' => false,
    ]);

    $middleware = new ShareTogglesWithInertia;
    $request = Request::create('/');

    $shared = $middleware->share($request);

    expect($shared['flags']['enabledFlag'])->toBeTrue()
        ->and($shared['flags']['disabledFlag'])->toBeFalse();
});
