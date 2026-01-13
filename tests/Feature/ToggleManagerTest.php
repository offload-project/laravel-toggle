<?php

declare(strict_types=1);

use OffloadProject\Toggle\Facades\Toggle;

it('returns true for active toggles', function () {
    expect(Toggle::active('test-flag'))->toBeTrue();
});

it('returns false for inactive toggles', function () {
    expect(Toggle::active('disabled-flag'))->toBeFalse();
});

it('returns inverse with inactive method', function () {
    expect(Toggle::inactive('test-flag'))->toBeFalse();
    expect(Toggle::inactive('disabled-flag'))->toBeTrue();
});

it('returns false for undefined toggles by default', function () {
    expect(Toggle::active('non-existent-flag'))->toBeFalse();
});

it('returns true for undefined toggles when configured', function () {
    config(['toggle.default' => 'true']);

    expect(Toggle::active('non-existent-flag'))->toBeTrue();
});

it('throws exception for undefined toggles when configured', function () {
    config(['toggle.default' => 'exception']);

    Toggle::active('non-existent-flag');
})->throws(OffloadProject\Toggle\Exceptions\ToggleNotFoundException::class);

it('returns all defined toggles', function () {
    $all = Toggle::all();

    expect($all)->toBeArray()
        ->and($all)->toHaveKey('test-flag')
        ->and($all)->toHaveKey('disabled-flag');
});
