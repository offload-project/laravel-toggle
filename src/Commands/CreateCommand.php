<?php

declare(strict_types=1);

namespace OffloadProject\Toggle\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use OffloadProject\Toggle\Models\Toggle;
use function Laravel\Prompts\error;
use function Laravel\Prompts\info;
use function Laravel\Prompts\warning;

class CreateCommand extends Command
{
    protected $signature = 'toggle:create
                            {name : The toggle name (kebab-case recommended)}
                            {--active : Set the toggle as active by default}
                            {--db : Also create a database record}';

    protected $description = 'Create a new feature toggle';

    public function __construct(
        protected Filesystem $files,
    )
    {
        parent::__construct();
    }

    public function handle(): int
    {
        /** @var string $name */
        $name = $this->argument('name');
        $active = (bool)$this->option('active');
        $createDb = (bool)$this->option('db');

        $envKey = $this->generateEnvKey($name);

        // Update config file
        $configUpdated = $this->updateConfigFile($name, $envKey, $active);

        // Update .env file
        $envUpdated = $this->updateEnvFile($envKey, $active);

        // Create database record if requested
        if ($createDb) {
            $this->createDatabaseRecord($name, $active);
        }

        // Show summary
        $this->showSummary($name, $envKey, $active, $configUpdated, $envUpdated, $createDb);

        return self::SUCCESS;
    }

    protected function generateEnvKey(string $name): string
    {
        return 'TOGGLE_' . strtoupper(str_replace('-', '_', $name));
    }

    protected function updateConfigFile(string $name, string $envKey, bool $active): bool
    {
        $configPath = config_path('toggle.php');

        if (!$this->files->exists($configPath)) {
            warning('Config file not found. Please publish it first:');
            info('php artisan vendor:publish --tag=toggle-config');

            return false;
        }

        try {
            $content = $this->files->get($configPath);
        } catch (FileNotFoundException) {
            error('Could not read config file.');

            return false;
        }

        // Check if flag already exists
        if (str_contains($content, "'{$name}'")) {
            warning("Toggle '{$name}' already exists in config.");

            return false;
        }

        $default = $active ? 'true' : 'false';
        $newEntry = "        '{$name}' => env('{$envKey}', {$default}),";

        // Find the flags array and insert the new entry
        $pattern = "/('flags'\s*=>\s*\[)([^]]*?)(\s*],)/s";

        if (!preg_match($pattern, $content, $matches)) {
            $this->showManualInstructions($name, $envKey, $active);

            return false;
        }

        $existingFlags = $matches[2];

        // Determine proper formatting
        if (trim($existingFlags) === '' || trim($existingFlags) === '// \'example-flag\' => env(\'TOGGLE_EXAMPLE_FLAG\', false),') {
            // Empty or only has example comment - replace with our entry
            $replacement = "$1\n{$newEntry}\n    $3";
            $content = preg_replace($pattern, $replacement, $content);
        } else {
            // Has existing entries - add to the end
            $replacement = "$1$2\n{$newEntry}$3";
            $content = preg_replace($pattern, $replacement, $content);
        }

        if ($content === null) {
            $this->showManualInstructions($name, $envKey, $active);

            return false;
        }

        $this->files->put($configPath, $content);

        return true;
    }

    protected function updateEnvFile(string $envKey, bool $active): bool
    {
        $envPath = base_path('.env');

        if (!$this->files->exists($envPath)) {
            return false;
        }

        try {
            $content = $this->files->get($envPath);
        } catch (FileNotFoundException) {
            return false;
        }

        // Check if key already exists
        if (preg_match("/^{$envKey}=/m", $content)) {
            warning("Environment variable '{$envKey}' already exists in .env");

            return false;
        }

        $value = $active ? 'true' : 'false';

        // Add to end of file
        $content = rtrim($content) . "\n{$envKey}={$value}\n";

        $this->files->put($envPath, $content);

        return true;
    }

    protected function createDatabaseRecord(string $name, bool $active): void
    {
        try {
            Toggle::updateOrCreate(
                ['name' => $name],
                ['active' => $active],
            );
            info("Database record created for '{$name}'");
        } catch (Exception $e) {
            error("Failed to create database record: {$e->getMessage()}");
            warning('Make sure the toggles table exists. Run: php artisan migrate');
        }
    }

    protected function showManualInstructions(string $name, string $envKey, bool $active): void
    {
        $default = $active ? 'true' : 'false';
        $value = $active ? 'true' : 'false';

        warning('Could not automatically update config file. Please add manually:');
        info('');
        info("Add to config/toggle.php in the 'flags' array:");
        info("    '{$name}' => env('{$envKey}', {$default}),");
        info('');
        info('Add to .env:');
        info("    {$envKey}={$value}");
    }

    protected function showSummary(
        string $name,
        string $envKey,
        bool   $active,
        bool   $configUpdated,
        bool   $envUpdated,
        bool   $createDb,
    ): void
    {
        info("Toggle '{$name}' created!");

        if ($configUpdated) {
            info('✓ Added to config/toggle.php');
        }

        if ($envUpdated) {
            info('✓ Added to .env');
        }

        if ($createDb) {
            info('✓ Database record created');
        }
        info('Usage:');
        info("Toggle::active('{$name}')");
        info("@toggle('{$name}') ... @endtoggle");
    }
}
