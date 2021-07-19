<?php
/**
 *
 * Schema definition for 'cabride_driver'
 *
 * Last update: 2018-10-26
 *
 */
$schemas = (!isset($schemas)) ? [] : $schemas;
$schemas['cabride_driver'] = [
    'driver_id' => [
        'type' => 'int(11) unsigned',
        'auto_increment' => true,
        'primary' => true,
    ],
    'value_id' => [
        'type' => 'int(11) unsigned',
        'foreign_key' => [
            'table' => 'application_option_value',
            'column' => 'value_id',
            'name' => 'FK_CABRIDE_DRIVER_VID_AOV_VID',
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
    'customer_id' => [
        'type' => 'int(11) unsigned',
        'foreign_key' => [
            'table' => 'customer',
            'column' => 'customer_id',
            'name' => 'FK_CABRIDE_CID_DRIVER_CID',
            'on_update' => 'CASCADE',
            'on_delete' => 'CASCADE',
        ],
        'index' => [
            'key_name' => 'customer_id',
            'index_type' => 'BTREE',
            'is_null' => false,
            'is_unique' => false,
        ],
    ],
    'vehicle_id' => [
        'type' => 'int(11) unsigned',
        'index' => [
            'key_name' => 'vehicle_id',
            'index_type' => 'BTREE',
            'is_null' => false,
            'is_unique' => false,
        ],
    ],
    'vehicle_model' => [
        'type' => 'varchar(128)',
        'charset' => 'utf-8',
        'collation' => 'utf8_unicode_ci',
    ],
    'vehicle_license_plate' => [
        'type' => 'varchar(128)',
        'charset' => 'utf-8',
        'collation' => 'utf8_unicode_ci',
    ],
    'driver_license' => [
        'type' => 'varchar(128)',
        'charset' => 'utf-8',
        'collation' => 'utf8_unicode_ci',
    ],
    'driver_photo' => [
        'type' => 'varchar(128)',
        'charset' => 'utf-8',
        'collation' => 'utf8_unicode_ci',
    ],
    /** @deprecated field mobile, uses customer mobile fields. */
    'driver_phone' => [
        'type' => 'varchar(20)',
        'charset' => 'utf-8',
        'collation' => 'utf8_unicode_ci',
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
    'pickup_radius' => [
        'type' => 'int(11)',
        'default' => '10',
    ],
    /** Temporary fields */
    'seats' => [
        'type' => 'int(11)',
        'default' => '3',
    ],
    'base_fare' => [
        'type' => 'float',
        'null' => true,
    ],
    'distance_fare' => [
        'type' => 'float',
        'null' => true,
    ],
    'time_fare' => [
        'type' => 'float',
        'null' => true,
    ],
    'extra_seat_fare' => [
        'type' => 'float',
        'null' => true,
    ],
    'seat_distance_fare' => [
        'type' => 'float',
        'null' => true,
    ],
    'seat_time_fare' => [
        'type' => 'float',
        'null' => true,
    ],
    'tour_base_fare' => [
        'type' => 'float',
        'null' => true,
    ],
    'tour_time_fare' => [
        'type' => 'float',
        'null' => true,
    ],
    'extra_seat_tour_base_fare' => [
        'type' => 'float',
        'null' => true,
    ],
    'extra_seat_tour_time_fare' => [
        'type' => 'float',
        'null' => true,
    ],
    /** Temporary fields */
    'latitude' => [
        'type' => 'float',
        'null' => true,
    ],
    'longitude' => [
        'type' => 'float',
        'null' => true,
    ],
    'is_online' => [
        'type' => 'tinyint(1)',
        'default' => '0',
    ],
    'status' => [
        'type' => 'varchar(64)',
        'default' => 'active',
    ],
];
