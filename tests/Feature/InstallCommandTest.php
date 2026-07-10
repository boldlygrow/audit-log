<?php

use Illuminate\Support\Facades\Artisan;

it('registers the audit-log:install command', function () {
    expect(array_keys(Artisan::all()))->toContain('audit-log:install');
});

it('cancels cleanly without writing files when declined', function () {
    $this->artisan('audit-log:install')
        ->expectsConfirmation('Publish the config file to config/audit-log.php?', 'no')
        ->expectsConfirmation('Generate an application model overlay that extends the package base model?', 'no')
        ->expectsConfirmation('Publish the database migration?', 'no')
        ->expectsQuestion('Database table name', 'audit_logs')
        ->expectsConfirmation('Proceed?', 'no')
        ->expectsOutputToContain('Installation cancelled.')
        ->assertExitCode(0);
});
