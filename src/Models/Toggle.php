<?php

declare(strict_types=1);

namespace OffloadProject\Toggle\Models;

use Illuminate\Database\Eloquent\Model;
use OffloadProject\Toggle\Facades\Toggle as ToggleFacade;

/**
 * @property string $name
 * @property bool $active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Toggle extends Model
{
    protected $fillable = [
        'name',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    public function getTable(): string
    {
        return config('toggle.table', 'toggles');
    }

    protected static function booted(): void
    {
        static::saved(function (Toggle $toggle): void {
            ToggleFacade::forgetCache($toggle->name);
        });

        static::deleted(function (Toggle $toggle): void {
            ToggleFacade::forgetCache($toggle->name);
        });
    }
}
