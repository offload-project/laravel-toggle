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
        $databaseFlagSet = $this->databaseFlagSet();

        $result = [];

        // Config flags from config driver only
        foreach ($this->configFlags as $flag => $value) {
            if (! isset($databaseFlagSet[$flag])) {
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

        // Unlisted flags from the default driver (reuse already-fetched results)
        $defaultAll = ($this->defaultDriver === $this->databaseDriver)
            ? $dbResults
            : (($this->defaultDriver === $this->configDriver) ? $configResults : $this->defaultDriver->all());

        foreach ($defaultAll as $flag => $value) {
            if (! array_key_exists($flag, $result)) {
                $result[$flag] = $value;
            }
        }

        return $result;
    }

    protected function driverFor(string $name): Driver
    {
        if (isset($this->databaseFlagSet()[$name])) {
            return $this->databaseDriver;
        }

        if (array_key_exists($name, $this->configFlags)) {
            return $this->configDriver;
        }

        return $this->defaultDriver;
    }

    /**
     * @return array<string, true>
     */
    protected function databaseFlagSet(): array
    {
        return array_fill_keys($this->databaseFlags, true);
    }
}
