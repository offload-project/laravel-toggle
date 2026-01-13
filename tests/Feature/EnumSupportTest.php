<?php

declare(strict_types=1);

use OffloadProject\Toggle\Facades\Toggle;

enum TestFeature: string
{
    case TestFlag = 'test-flag';
    case DisabledFlag = 'disabled-flag';
    case NonExistent = 'non-existent';
}

it('accepts backed enum for active check', function () {
    expect(Toggle::active(TestFeature::TestFlag))->toBeTrue()
        ->and(Toggle::active(TestFeature::DisabledFlag))->toBeFalse();
});

it('accepts backed enum for inactive check', function () {
    expect(Toggle::inactive(TestFeature::TestFlag))->toBeFalse()
        ->and(Toggle::inactive(TestFeature::DisabledFlag))->toBeTrue();
});

it('handles undefined enum values', function () {
    expect(Toggle::active(TestFeature::NonExistent))->toBeFalse();
});
