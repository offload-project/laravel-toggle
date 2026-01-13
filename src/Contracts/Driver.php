<?php

declare(strict_types=1);

namespace OffloadProject\Toggle\Contracts;

interface Driver
{
    /**
     * Check if a flag is active.
     *
     * @return bool|null Returns null if the flag is not defined by this driver
     */
    public function get(string $name): ?bool;

    /**
     * Set a flag's value.
     *
     * @return bool Whether the operation was successful
     */
    public function set(string $name, bool $active): bool;

    /**
     * Delete a flag.
     *
     * @return bool Whether the operation was successful
     */
    public function delete(string $name): bool;

    /**
     * Check if a flag exists in this driver.
     */
    public function has(string $name): bool;

    /**
     * Get all flags from this driver.
     *
     * @return array<string, bool>
     */
    public function all(): array;
}
