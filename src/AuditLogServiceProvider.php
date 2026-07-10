<?php

namespace BoldlyGrow\AuditLog;

use Illuminate\Support\ServiceProvider;

class AuditLogServiceProvider extends ServiceProvider
{
    // use ServiceBindings;

    public function boot(): void
    {
        $this->bootRoutes();
        $this->publishConfigFile();
        $this->publishMigrations();
    }

    public function register()
    {
        $this->mergeConfig();
        $this->registerServices();
    }

    /**
     * Register the package routes.
     *
     * @return void
     */
    protected function bootRoutes()
    {
        // $this->loadRoutesFrom(__DIR__.'/Routes/console.php');
    }

    /**
     * Merge package config file into application config file
     *
     * This allows users to override any module configuration values with their
     * own values in the application config file.
     */
    protected function mergeConfig(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/Config/audit-log.php', 'audit-log');
    }

    /**
     * Publish config file to application
     *
     * Registered under two tags: `audit-log` (the umbrella tag shared with the
     * migration) and `audit-log-config` (targets the config file on its own).
     */
    protected function publishConfigFile(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes(
                [__DIR__ . '/Config/audit-log.php' => config_path('audit-log.php')],
                ['audit-log', 'audit-log-config']
            );
        }
    }

    /**
     * Register the publishable database migration.
     *
     * The migration is published rather than auto-loaded so that a single copy
     * owns the table (avoiding duplicate-run conflicts) and applications may
     * customize the schema. It is registered under two tags:
     *
     *   - `audit-log` — the umbrella tag shared with the config file, so
     *     `vendor:publish --tag=audit-log` publishes both in one step.
     *   - `audit-log-migrations` — targets the migration on its own.
     *
     * `publishesMigrations()` rewrites the migration's `0001_01_01_000000_` date
     * prefix to the current timestamp on publish when the application's
     * `database.migrations.update_date_on_publish` config is enabled (the
     * default in current Laravel skeletons).
     */
    protected function publishMigrations(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishesMigrations(
                [__DIR__ . '/Database/Migrations' => database_path('migrations')],
                ['audit-log', 'audit-log-migrations']
            );
        }
    }

    /**
     * Register package services in the container.
     *
     * @return void
     */
    protected function registerServices()
    {
        if (property_exists($this, 'serviceBindings')) {
            foreach ($this->serviceBindings as $key => $value) {
                is_numeric($key)
                    ? $this->app->singleton($value)
                    : $this->app->singleton($key, $value);
            }
        }
    }
}
