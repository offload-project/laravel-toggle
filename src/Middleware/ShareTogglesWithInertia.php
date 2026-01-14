<?php

declare(strict_types=1);

namespace OffloadProject\Toggle\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Middleware;
use OffloadProject\Toggle\Facades\Toggle;

class ShareTogglesWithInertia extends Middleware
{
    /**
     * Share Toggle feature flags with Inertia
     */
    public function share(Request $request): array
    {
        $flags = collect(Toggle::all())
            ->mapWithKeys(fn (bool $value, string $key) => [Str::camel($key) => $value])
            ->all();

        return [
            ...parent::share($request),
            'flags' => $flags,
        ];
    }
}
