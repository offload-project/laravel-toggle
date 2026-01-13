<?php

declare(strict_types=1);

namespace OffloadProject\Toggle\Exceptions;

use RuntimeException;

class ReadOnlyDriverException extends RuntimeException
{
    public static function cannotSet(string $name): self
    {
        return new self(
            "Cannot set toggle '{$name}': the config driver is read-only. Use the database driver or set values via environment variables."
        );
    }

    public static function cannotDelete(string $name): self
    {
        return new self(
            "Cannot delete toggle '{$name}': the config driver is read-only. Use the database driver to manage toggles at runtime."
        );
    }
}
