<?php

declare(strict_types=1);

namespace OffloadProject\Toggle\Commands;

use Illuminate\Console\Command;
use OffloadProject\Toggle\ToggleManager;

use function Laravel\Prompts\info;

class CacheClearCommand extends Command
{
    protected $signature = 'toggle:cache-clear
                            {name? : Clear cache for a specific toggle (clears all if omitted)}';

    protected $description = 'Clear the toggle cache';

    public function handle(ToggleManager $manager): int
    {
        /** @var string|null $name */
        $name = $this->argument('name');

        if ($name !== null) {
            $manager->forgetCache($name);
            info("Cache cleared for toggle '{$name}'");
        } else {
            $manager->flushCache();
            info('All toggle caches cleared');
        }

        return self::SUCCESS;
    }
}
