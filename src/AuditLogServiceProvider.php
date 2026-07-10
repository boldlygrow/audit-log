<?php

namespace BoldlyGrow\AuditLog;

use BoldlyGrow\AuditLog\Console\InstallCommand;
use Illuminate\Support\ServiceProvider;

class AuditLogServiceProvider extends ServiceProvider
{
    // use ServiceBindings;

    public function boot(): void
    {
        $this->bootRoutes();
        $this->publishConfigFile();
        $this->publishMigrations();
        $this->registerCommands();
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
        //$this->loadRoutesFrom(__DIR__.'/Routes/console.php');
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
     * Once the `php artisan vendor::publish` command is run, you can use the
     * configuration file values `$value = config('audit.option');`
     */
    protected function publishConfigFile(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes(
                [__DIR__ . '/Config/audit-log.php' => config_path('audit-log.php')],
                'audit-log'
            );
        }
    }

    /**
     * Register the publishable database migration.
     *
     * The migration is published rather than auto-loaded so that a single copy
     * owns the table (avoiding duplicate-run conflicts) and applications may
     * customize the schema. Use `php artisan audit-log:install` for an
     * interactive publish, or `vendor:publish --tag=audit-log-migrations`.
     */
    protected function publishMigrations(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes(
                [__DIR__ . '/Database/Migrations' => database_path('migrations')],
                'audit-log-migrations'
            );
        }
    }

    /**
     * Register the package console commands.
     */
    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
            ]);
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
