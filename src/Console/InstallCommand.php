<?php

/**
 * @copyright Jefferson Martin
 * @license MIT <https://spdx.org/licenses/MIT.html>
 * @link https://github.com/boldlygrow/audit-log
 */

namespace BoldlyGrow\AuditLog\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class InstallCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'audit-log:install';

    /**
     * @var string
     */
    protected $description = 'Publish the audit-log config, an application model overlay, and the database migration';

    public function handle(Filesystem $files): int
    {
        $this->info('Boldly Grow Audit Log installer');
        $this->line('This will publish the config, an application model overlay, and the database migration.');
        $this->newLine();

        // 1. Configuration file
        $publishConfig = $this->confirm('Publish the config file to config/audit-log.php?', true);

        // 2. Application model overlay
        $modelClass = null;
        $modelPath = null;
        if ($this->confirm('Generate an application model overlay that extends the package base model?', true)) {
            $modelClass = trim((string) $this->ask('Fully-qualified model class', 'App\\Models\\AuditLog'), '\\');
            $modelPath = $this->classToPath($modelClass);
        }

        // 3. Migration
        $publishMigration = $this->confirm('Publish the database migration?', true);

        // 4. Table name
        $table = (string) $this->ask('Database table name', (string) config('audit-log.database.table', 'audit_logs'));

        // Summary and confirmation
        $this->newLine();
        $this->line('<comment>Summary</comment>');
        $this->line('  Config file:  ' . ($publishConfig ? 'config/audit-log.php' : 'skip'));
        $this->line('  Model overlay: ' . ($modelClass ? $modelClass . ' (' . $this->relativePath($modelPath) . ')' : 'skip'));
        $this->line('  Migration:    ' . ($publishMigration ? 'database/migrations' : 'skip'));
        $this->line('  Table name:   ' . $table);
        $this->newLine();

        if (!$this->confirm('Proceed?', true)) {
            $this->warn('Installation cancelled.');

            return self::SUCCESS;
        }

        if ($publishConfig) {
            $this->callSilently('vendor:publish', ['--tag' => 'audit-log']);
            $this->info('Published config/audit-log.php');
        }

        if ($modelClass && $modelPath) {
            $this->writeModel($files, $modelClass, $modelPath);
        }

        if ($publishMigration) {
            $this->writeMigration($files);
        }

        $this->newLine();
        $this->info('Audit log installed.');
        $this->line('Next steps:');
        $this->line('  1. Set <comment>AUDIT_DATABASE_ENABLED=true</comment> in your .env to persist entries.');
        if ($modelClass) {
            $this->line("  2. Set <comment>config('audit-log.database.model')</comment> to <comment>\\{$modelClass}::class</comment>.");
        }
        if ($table !== config('audit-log.database.table', 'audit_logs')) {
            $this->line("  3. Set <comment>AUDIT_DATABASE_TABLE={$table}</comment> in your .env.");
        }
        $this->line('  4. Run <comment>php artisan migrate</comment>.');

        return self::SUCCESS;
    }

    /**
     * Generate the application model overlay from the stub.
     */
    private function writeModel(Filesystem $files, string $modelClass, string $modelPath): void
    {
        if ($files->exists($modelPath) && !$this->confirm($this->relativePath($modelPath) . ' already exists. Overwrite?', false)) {
            $this->warn('Skipped model overlay (already exists).');

            return;
        }

        $namespace = implode('\\', array_slice(explode('\\', $modelClass), 0, -1));
        $class = class_basename($modelClass);

        $contents = str_replace(
            ['{{ namespace }}', '{{ class }}'],
            [$namespace, $class],
            $files->get(__DIR__ . '/stubs/model.stub')
        );

        $files->ensureDirectoryExists(dirname($modelPath));
        $files->put($modelPath, $contents);

        $this->info('Created model overlay ' . $this->relativePath($modelPath));
    }

    /**
     * Copy the package migration into the application with a fresh timestamp.
     */
    private function writeMigration(Filesystem $files): void
    {
        $source = __DIR__ . '/../Database/Migrations/0001_01_01_000000_create_audit_logs_table.php';
        $directory = database_path('migrations');

        $existing = $files->glob($directory . '/*_create_audit_logs_table.php');

        if (!empty($existing) && !$this->confirm('A create_audit_logs_table migration already exists. Publish another?', false)) {
            $this->warn('Skipped migration (already exists).');

            return;
        }

        $files->ensureDirectoryExists($directory);
        $target = $directory . '/' . date('Y_m_d_His') . '_create_audit_logs_table.php';
        $files->copy($source, $target);

        $this->info('Published migration ' . $this->relativePath($target));
    }

    /**
     * Convert a fully-qualified class name to a file path (App\ maps to app/).
     */
    private function classToPath(string $class): string
    {
        $segments = explode('\\', trim($class, '\\'));
        $segments[0] = lcfirst($segments[0]);

        return base_path(implode('/', $segments) . '.php');
    }

    /**
     * Present a path relative to the application base for readability.
     */
    private function relativePath(string $path): string
    {
        return ltrim(str_replace(base_path(), '', $path), '/\\');
    }
}
