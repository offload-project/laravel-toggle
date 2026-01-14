<?php

declare(strict_types=1);

namespace OffloadProject\Toggle\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use OffloadProject\Toggle\Facades\Toggle;

class ShareTogglesWithInertia extends Middleware
{
    /**
     * Share Toggle feature flags with Inertia
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'flags' => Toggle::all(),
        ];
    }
}
