<?php

return [
    'defaults' => [
        'courts_count' => 2,
        'court_label_prefix' => 'Court',
        'schedule_start_time' => '09:00',
        'schedule_end_time' => '18:00',
        'match_duration_minutes' => 30,
        'rest_minutes' => 10,
        'max_matches_per_player_per_day' => 4,
    ],

    'samples' => [
        'single_elimination' => [
            'group_size' => null,
            'qualifiers_per_pool' => null,
            'days' => [
                ['label' => 'Main draw', 'allowed_stages' => ['main'], 'allowed_rounds' => []],
            ],
        ],
        'double_elimination' => [
            'group_size' => null,
            'qualifiers_per_pool' => null,
            'days' => [
                ['label' => 'Winners bracket', 'allowed_stages' => ['winners'], 'allowed_rounds' => []],
                ['label' => 'Losers bracket', 'allowed_stages' => ['losers'], 'allowed_rounds' => []],
            ],
        ],
        'round_robin' => [
            'group_size' => 4,
            'qualifiers_per_pool' => null,
            'days' => [
                ['label' => 'Pool matches', 'allowed_stages' => ['pool'], 'allowed_rounds' => []],
            ],
        ],
        'pool_to_knockout' => [
            'group_size' => 4,
            'qualifiers_per_pool' => 2,
            'days' => [
                ['label' => 'Pool matches', 'allowed_stages' => ['pool'], 'allowed_rounds' => []],
                ['label' => 'Knockout opening', 'allowed_stages' => ['knockout'], 'allowed_rounds' => ['1']],
                ['label' => 'Final rounds', 'allowed_stages' => ['knockout'], 'allowed_rounds' => ['2', '3', '4']],
            ],
        ],
    ],
];
