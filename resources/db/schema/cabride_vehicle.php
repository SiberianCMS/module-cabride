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
    ],'value_id' => [
        'type' => 'int(11) unsigned',
        'foreign_key' => [
            'table' => 'application_option_value',
            'column' => 'value_id',
            'name' => 'FK_CABRIDE_VEHICLE_VID_AOV_VID',
            'on_update' => 'CASCADE',
            'on_delete' => 'CASCADE',
        ],
        'index' => [
            'key_name' => 'value_id',
            'index_type' => 'BTREE',
            'is_null' => false,
            'is_unique' => false,
        ],
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
