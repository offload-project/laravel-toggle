<?php

declare(strict_types=1);

namespace OffloadProject\Toggle\Facades;

use BackedEnum;
use Illuminate\Support\Facades\Facade;
use OffloadProject\Toggle\Contracts\Driver;
use OffloadProject\Toggle\ToggleManager;

/**
 * @method static bool active(string|BackedEnum $name)
 * @method static bool inactive(string|BackedEnum $name)
 * @method static bool enable(string|BackedEnum $name)
 * @method static bool disable(string|BackedEnum $name)
 * @method static bool delete(string|BackedEnum $name)
 * @method static array<string, bool> all()
 * @method static bool forgetCache(string|BackedEnum $name)
 * @method static bool flushCache()
 * @method static ToggleManager setDriver(Driver $driver)
 * @method static Driver getDriver()
 *
 * @see ToggleManager
 */
class Toggle extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ToggleManager::class;
    }
}
