<?php
/**
 *
 * Schema definition for 'cabride_vehicle'
 *
 * Last update: 2018-10-26
 *
 */
$schemas = (!isset($schemas)) ? [] : $schemas;
$schemas['cabride_vehicle'] = [
    'vehicle_id' => [
        'type' => 'int(11) unsigned',
        'auto_increment' => true,
        'primary' => true,
    ],
    'type' => [
        'type' => 'varchar(128)',
    ],
    'base_fare' => [
        'type' => 'float',
    ],
    'distance_fare' => [
        'type' => 'float',
    ],
    'time_fare' => [
        'type' => 'float',
    ],
    'base_address' => [
        'type' => 'text',
    ],
    'is_visible' => [
        'type' => 'tinyint(1)',
        'default' => '1',
    ],
];
