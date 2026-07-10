<?php

use BoldlyGrow\AuditLog\Models\AuditLog;

return [
    'actor' => [
        'enabled' => true,

        /*
         | The request headers (in order of preference) that contain the actor's
         | originating IP address when the application is behind a proxy or CDN.
         | The first non-empty header wins, falling back to request()->ip().
         */
        'ip_headers' => [
            // 'CF-Connecting-IP',
            // 'X-Forwarded-For',
        ],

        'source' => [
            'enabled' => true,

            /*
             | The allowed actor source values. This is the single place to tweak
             | the vocabulary. The auto-detector emits `system`, `api`, or `web`;
             | `cli` (and any custom values you add) can be passed explicitly to
             | AuditLog::create(actor_source: '...'). Passed values are validated
             | against this list.
             */
            'allowed' => [
                'system',
                'cli',
                'api',
                'web',
            ],
        ],

        /*
         | The attribute(s) read from the authenticated user model to populate
         | each actor field. Override these if your user model uses different
         | column names (for example `work_email` or `full_name`). Each value
         | may be a single attribute name or an ordered list of candidates, in
         | which case the first non-null value wins.
         */
        'attributes' => [
            'id' => 'id',
            'email' => 'email',
            'name' => ['name', 'full_name'],
            'provider_id' => 'provider_id',
            'username' => 'username',
        ],
    ],

    'model' => [
        /*
         | The namespace stripped from a fully-qualified model class name before
         | it is converted to a snake_case `*_type`. For example, with the default
         | below, `App\Models\Okta\User` becomes `okta_user`.
         */
        'namespace' => 'App\\Models\\',
    ],

    'database' => [
        /*
         | Whether audit events passed with `database: true` are persisted to the
         | database. This is a per-application "set it and forget it" switch. If
         | your application cannot support a database (for example a Laravel Zero
         | CLI app), set this to false.
         */
        'enabled' => true,

        /*
         | The Eloquent model used to persist audit log entries. The package ships
         | a working model; to add relationships or casts, create your own model
         | that extends it and point this value at your class.
         */
        'model' => AuditLog::class,

        'table' => 'audit_logs',

        /*
         | Additional metadata keys that should be flattened out of the `metadata`
         | array into their own database columns (which you add via your own
         | migration). This lets you persist application-specific fields without
         | modifying the package. Whitelisted keys remain in the `metadata` JSON
         | for the system log as well.
         */
        'custom_fields' => [
            // 'federation_organization_id',
            // 'fleet_cell_id',
        ],
    ],

    'dump' => [
        'default' => [
            'date' => 'c',
            'strings' => [
                // 'custom_key' => 'my_value'
            ],
            'keys' => [
                'event_type',
                'message',
                'record_id',
                'record_type',
                'record_provider_id',
                'record_reference_key',
                'record_reference_value',
            ],
        ],
        // 'example_key' => [
        //     'date' => 'c',
        //     'strings' => [
        //         'custom_key' => 'my_value'
        //     ],
        //     'keys' => [
        //         'event_type',
        //         'message',
        //         'attribute_key',
        //         'attribute_value_old',
        //         'attribute_value_new',
        //         'record_id',
        //         'record_type',
        //         'record_provider_id',
        //         'record_reference_key',
        //         'record_reference_value'
        //     ],
        // ],
    ],
];
