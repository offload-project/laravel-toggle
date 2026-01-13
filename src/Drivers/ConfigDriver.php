<?php

declare(strict_types=1);

namespace OffloadProject\Toggle\Drivers;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use OffloadProject\Toggle\Contracts\Driver;
use OffloadProject\Toggle\Exceptions\ReadOnlyDriverException;

class ConfigDriver implements Driver
{
    public function __construct(
        protected ConfigRepository $config,
    ) {}

    public function get(string $name): ?bool
    {
        $flags = $this->config->get('toggle.flags', []);

        if (! array_key_exists($name, $flags)) {
            return null;
        }

        return (bool) $flags[$name];
    }

    public function set(string $name, bool $active): bool
    {
        throw ReadOnlyDriverException::cannotSet($name);
    }

    public function delete(string $name): bool
    {
        throw ReadOnlyDriverException::cannotDelete($name);
    }

    public function has(string $name): bool
    {
        $flags = $this->config->get('toggle.flags', []);

        return array_key_exists($name, $flags);
    }

    public function all(): array
    {
        $flags = $this->config->get('toggle.flags', []);

        return array_map(fn ($value) => (bool) $value, $flags);
    }
}
