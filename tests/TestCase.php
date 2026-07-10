<?php

namespace BoldlyGrow\AuditLog\Tests;

use BoldlyGrow\AuditLog\AuditLogServiceProvider;
use BoldlyGrow\AuditLog\Models\AuditLog as AuditLogModel;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    /**
     * @param  \Illuminate\Foundation\Application  $app
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [AuditLogServiceProvider::class];
    }

    /**
     * @param  \Illuminate\Foundation\Application  $app
     */
    protected function defineEnvironment($app): void
    {
        $app['config']->set('audit-log.database.enabled', true);
        $app['config']->set('audit-log.database.model', AuditLogModel::class);
        $app['config']->set('audit-log.database.custom_fields', ['custom_test_field']);
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/../src/Database/Migrations');

        // Simulate an application-added custom column for the custom-fields test.
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->string('custom_test_field')->nullable();
        });
    }
}
