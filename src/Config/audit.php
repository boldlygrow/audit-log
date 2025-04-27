<?php

return [
    'actor' => [
        // @phpstan-ignore larastan.noEnvCallsOutsideOfConfig
        'enabled' => env('AUDIT_ACTOR_ENABLED', true)
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
                'record_reference_value'
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
    ]
];
