<?php

declare(strict_types=1);

namespace OffloadProject\Toggle;

use BackedEnum;
use Illuminate\Cache\CacheManager;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use OffloadProject\Toggle\Contracts\Driver;
use OffloadProject\Toggle\Exceptions\ToggleNotFoundException;
use RuntimeException;

class ToggleManager
{
    protected ?CacheRepository $cache = null;

    protected ?Driver $driver = null;

    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        protected array $config,
        protected CacheManager $cacheManager,
    ) {}

    /**
     * Check if a toggle is active.
     */
    public function active(string|BackedEnum $name): bool
    {
        $key = $this->normalizeKey($name);

        if ($this->cacheEnabled()) {
            return $this->getCache()->remember(
                $this->cacheKey($key),
                $this->cacheTtl(),
                fn () => $this->resolve($key)
            );
        }

        return $this->resolve($key);
    }

    /**
     * Check if a toggle is inactive.
     */
    public function inactive(string|BackedEnum $name): bool
    {
        return ! $this->active($name);
    }

    /**
     * Enable a toggle (database driver only).
     */
    public function enable(string|BackedEnum $name): bool
    {
        $key = $this->normalizeKey($name);
        $result = $this->getDriver()->set($key, true);

        if ($result) {
            $this->forgetCache($key);
        }

        return $result;
    }

    /**
     * Disable a toggle (database driver only).
     */
    public function disable(string|BackedEnum $name): bool
    {
        $key = $this->normalizeKey($name);
        $result = $this->getDriver()->set($key, false);

        if ($result) {
            $this->forgetCache($key);
        }

        return $result;
    }

    /**
     * Delete a toggle from the database.
     */
    public function delete(string|BackedEnum $name): bool
    {
        $key = $this->normalizeKey($name);
        $result = $this->getDriver()->delete($key);

        if ($result) {
            $this->forgetCache($key);
        }

        return $result;
    }

    /**
     * Get all defined toggles.
     *
     * @return array<string, bool>
     */
    public function all(): array
    {
        return $this->getDriver()->all();
    }

    /**
     * Clear cache for a specific toggle.
     */
    public function forgetCache(string|BackedEnum $name): bool
    {
        $key = $this->normalizeKey($name);

        return $this->getCache()->forget($this->cacheKey($key));
    }

    /**
     * Clear cache for all toggles.
     */
    public function flushCache(): bool
    {
        $cache = $this->getCache();

        // Clear all known toggles from cache
        foreach ($this->getDriver()->all() as $name => $value) {
            $cache->forget($this->cacheKey($name));
        }

        return true;
    }

    /**
     * Set the driver instance.
     */
    public function setDriver(Driver $driver): static
    {
        $this->driver = $driver;

        return $this;
    }

    /**
     * Get the current driver instance.
     */
    public function getDriver(): Driver
    {
        if ($this->driver === null) {
            throw new RuntimeException('Toggle driver has not been set.');
        }

        return $this->driver;
    }

    /**
     * Resolve a toggle value from the driver.
     */
    protected function resolve(string $name): bool
    {
        $value = $this->getDriver()->get($name);

        if ($value !== null) {
            return $value;
        }

        return $this->handleUndefined($name);
    }

    /**
     * Handle undefined toggle based on config.
     */
    protected function handleUndefined(string $name): bool
    {
        $default = $this->config['default'] ?? 'false';

        return match ($default) {
            'true' => true,
            'exception' => throw new ToggleNotFoundException("Toggle '{$name}' is not defined."),
            default => false,
        };
    }

    /**
     * Normalize a toggle key from string or enum.
     */
    protected function normalizeKey(string|BackedEnum $name): string
    {
        if ($name instanceof BackedEnum) {
            return (string) $name->value;
        }

        return $name;
    }

    /**
     * Get the cache repository.
     */
    protected function getCache(): CacheRepository
    {
        if ($this->cache === null) {
            $store = $this->config['cache']['store'] ?? null;
            $this->cache = $this->cacheManager->store($store);
        }

        return $this->cache;
    }

    /**
     * Check if caching is enabled.
     */
    protected function cacheEnabled(): bool
    {
        return (bool) ($this->config['cache']['enabled'] ?? true);
    }

    /**
     * Get the cache TTL in seconds.
     */
    protected function cacheTtl(): int
    {
        return (int) ($this->config['cache']['ttl'] ?? 3600);
    }

    /**
     * Generate a cache key for a toggle.
     */
    protected function cacheKey(string $name): string
    {
        $prefix = $this->config['cache']['prefix'] ?? 'toggle:';

        return $prefix.$name;
    }
}
