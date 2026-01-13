<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    // Clear compiled views to ensure fresh compilation
    $viewPath = storage_path('framework/views');
    if (File::isDirectory($viewPath)) {
        File::cleanDirectory($viewPath);
    }
});

it('renders content when toggle is active', function () {
    $blade = "@toggle('test-flag') Active Content @endtoggle";
    $rendered = Blade::render($blade);

    expect(trim($rendered))->toBe('Active Content');
});

it('does not render content when toggle is inactive', function () {
    $blade = "@toggle('disabled-flag') Hidden Content @endtoggle";
    $rendered = Blade::render($blade);

    expect(trim($rendered))->toBe('');
});

it('supports else directive', function () {
    $blade = "@toggle('disabled-flag') Active @elsetoggle Inactive @endtoggle";
    $rendered = Blade::render($blade);

    expect(trim($rendered))->toBe('Inactive');
});
