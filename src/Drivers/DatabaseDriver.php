<?php

declare(strict_types=1);

namespace OffloadProject\Toggle\Drivers;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\QueryException;
use OffloadProject\Toggle\Contracts\Driver;

class DatabaseDriver implements Driver
{
    protected ConfigDriver $configDriver;

    public function __construct(
        protected ConnectionInterface $connection,
        protected ConfigRepository $config,
    ) {
        $this->configDriver = new ConfigDriver($config);
    }

    public function get(string $name): ?bool
    {
        // Check database first
        $dbValue = $this->getFromDatabase($name);

        if ($dbValue !== null) {
            return $dbValue;
        }

        // Fall back to config
        return $this->configDriver->get($name);
    }

    public function set(string $name, bool $active): bool
    {
        try {
            $this->connection->table($this->getTable())->updateOrInsert(
                ['name' => $name],
                ['active' => $active, 'updated_at' => now()],
            );

            return true;
        } catch (QueryException) {
            return false;
        }
    }

    public function delete(string $name): bool
    {
        try {
            $this->connection->table($this->getTable())
                ->where('name', $name)
                ->delete();

            return true;
        } catch (QueryException) {
            return false;
        }
    }

    public function has(string $name): bool
    {
        return $this->hasInDatabase($name) || $this->configDriver->has($name);
    }

    public function all(): array
    {
        // Merge config flags with database flags (database takes precedence)
        $configFlags = $this->configDriver->all();
        $dbFlags = $this->getAllFromDatabase();

        return array_merge($configFlags, $dbFlags);
    }

    protected function getFromDatabase(string $name): ?bool
    {
        try {
            $row = $this->connection->table($this->getTable())
                ->where('name', $name)
                ->first();

            if ($row === null) {
                return null;
            }

            return (bool) $row->active;
        } catch (QueryException) {
            // Table doesn't exist or other DB error, fall back gracefully
            return null;
        }
    }

    protected function hasInDatabase(string $name): bool
    {
        try {
            return $this->connection->table($this->getTable())
                ->where('name', $name)
                ->exists();
        } catch (QueryException) {
            return false;
        }
    }

    /**
     * @return array<string, bool>
     */
    protected function getAllFromDatabase(): array
    {
        try {
            return $this->connection->table($this->getTable())
                ->pluck('active', 'name')
                ->map(fn ($value) => (bool) $value)
                ->all();
        } catch (QueryException) {
            return [];
        }
    }

    protected function getTable(): string
    {
        return $this->config->get('toggle.table', 'toggles');
    }
}
