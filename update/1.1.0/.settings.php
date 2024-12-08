<?php
return [
    'ui.entity-selector' => [
        'value' => [
            'entities' => [
                [
                    'entityId' => 'awzcurrency-user',
                    'provider' => [
                        'moduleId' => 'awz.currency',
                        'className' => '\\Awz\\Currency\\Access\\EntitySelectors\\User'
                    ],
                ],
                [
                    'entityId' => 'awzcurrency-group',
                    'provider' => [
                        'moduleId' => 'awz.currency',
                        'className' => '\\Awz\\Currency\\Access\\EntitySelectors\\Group'
                    ],
                ],
            ]
        ],
        'readonly' => true,
    ]
];