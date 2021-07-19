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
    'value_id' => [
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
        'charset' => 'utf-8',
        'collation' => 'utf8_unicode_ci',
    ],
    'icon' => [
        'type' => 'varchar(255)',
        'charset' => 'utf-8',
        'collation' => 'utf8_unicode_ci',
        'null' => true,
    ],
    'base_address' => [
        'type' => 'text',
        'null' => true,
    ],
    'base_address_parts' => [
        'type' => 'text',
        'null' => true,
    ],
    'base_latitude' => [
        'type' => 'float',
        'null' => true,
    ],
    'base_longitude' => [
        'type' => 'float',
        'null' => true,
    ],
    'seats' => [
        'type' => 'int(11)',
        'default' => '3',
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
    'extra_seat_fare' => [
        'type' => 'float',
    ],
    'seat_distance_fare' => [
        'type' => 'float',
    ],
    'seat_time_fare' => [
        'type' => 'float',
    ],
    'tour_base_fare' => [
        'type' => 'float',
    ],
    'extra_seat_tour_base_fare' => [
        'type' => 'float',
    ],
    'tour_time_fare' => [
        'type' => 'float',
    ],
    'extra_seat_tour_time_fare' => [
        'type' => 'float',
    ],
    'is_visible' => [
        'type' => 'tinyint(1)',
        'default' => '1',
    ],
];
