<?php

declare(strict_types=1);

namespace OffloadProject\Toggle\Commands;

use Illuminate\Console\Command;
use OffloadProject\Toggle\ToggleManager;

use function Laravel\Prompts\table;

class ListCommand extends Command
{
    protected $signature = 'toggle:list';

    protected $description = 'List all feature toggles and their current state';

    public function handle(ToggleManager $manager): int
    {
        $toggles = $manager->all();

        if (empty($toggles)) {
            $this->components->info('No toggles defined.');

            return self::SUCCESS;
        }

        table(
            headers: ['Toggle', 'Status'],
            rows: collect($toggles)
                ->map(fn (bool $active, string $name) => [
                    $name,
                    $active ? '<fg=green>enabled</>' : '<fg=red>disabled</>',
                ])
                ->values()
                ->all(),
        );

        return self::SUCCESS;
    }
}
