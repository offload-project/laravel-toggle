<?php

declare(strict_types=1);

namespace OffloadProject\Toggle\Drivers;

use OffloadProject\Toggle\Contracts\Driver;

class PerFlagDriver implements Driver
{
    /**
     * @param  array<int, string>  $databaseFlags
     * @param  array<string, mixed>  $configFlags
     */
    public function __construct(
        protected ConfigDriver $configDriver,
        protected DatabaseDriver $databaseDriver,
        protected Driver $defaultDriver,
        protected array $databaseFlags,
        protected array $configFlags,
    ) {}

    public function get(string $name): ?bool
    {
        return $this->driverFor($name)->get($name);
    }

    public function set(string $name, bool $active): bool
    {
        return $this->driverFor($name)->set($name, $active);
    }

    public function delete(string $name): bool
    {
        return $this->driverFor($name)->delete($name);
    }

    public function has(string $name): bool
    {
        return $this->driverFor($name)->has($name);
    }

    public function all(): array
    {
        $configResults = $this->configDriver->all();
        $dbResults = $this->databaseDriver->all();

        // Config flags from config driver only
        $result = [];

        foreach ($this->configFlags as $flag => $value) {
            if (! in_array($flag, $this->databaseFlags, true)) {
                $result[$flag] = $configResults[$flag] ?? (bool) $value;
            }
        }

        // Database flags from database driver
        foreach ($this->databaseFlags as $flag) {
            if (isset($dbResults[$flag])) {
                $result[$flag] = $dbResults[$flag];
            } elseif (isset($configResults[$flag])) {
                $result[$flag] = $configResults[$flag];
            }
        }

        // Unlisted flags from the default driver
        $defaultAll = $this->defaultDriver->all();

        foreach ($defaultAll as $flag => $value) {
            if (! array_key_exists($flag, $result)) {
                $result[$flag] = $value;
            }
        }

        return $result;
    }

    protected function driverFor(string $name): Driver
    {
        if (in_array($name, $this->databaseFlags, true)) {
            return $this->databaseDriver;
        }

        if (array_key_exists($name, $this->configFlags)) {
            return $this->configDriver;
        }

        return $this->defaultDriver;
    }
}
