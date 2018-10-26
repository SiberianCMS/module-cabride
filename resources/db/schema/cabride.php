<?php
/**
 *
 * Schema definition for 'cabride'
 *
 * Last update: 2018-10-26
 *
 */
$schemas = (!isset($schemas)) ? [] : $schemas;
$schemas['cabride'] = [
    'cabride_id' => [
        'type' => 'int(11) unsigned',
        'auto_increment' => true,
        'primary' => true,
    ],
    'value_id' => [
        'type' => 'int(11) unsigned',
        'foreign_key' => [
            'table' => 'application_option_value',
            'column' => 'value_id',
            'name' => 'FK_CABRIDE_VID_AOV_VID',
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
    'distance_unit' => [ // km or miles
        'type' => 'varchar(16)',
        'charset' => 'utf8',
        'collation' => 'utf8_unicode_ci',
        'default' => 'km',
    ],
    'search_timeout' => [ // 60 seconds
        'type' => 'int',
        'default' => '60'
    ],
    'search_radius' => [
        'type' => 'float',
        'default' => '10'
    ],
    'accepted_payments' => [ // CB, Cash, All
        'type' => 'text',
        'charset' => 'utf-8',
        'collation' => 'utf8_unicode_ci',
    ],
    'commission' => [
        'type' => 'varchar(256)',
        'charset' => 'utf-8',
        'collation' => 'utf8_unicode_ci',
    ],
    'course_mode' => [ // Immediate, Booked
        'type' => 'varchar(256)',
        'charset' => 'utf-8',
        'collation' => 'utf8_unicode_ci',
    ],
    'pricing_mode' => [ // Admin, Driver
        'type' => 'varchar(256)',
        'charset' => 'utf-8',
        'collation' => 'utf8_unicode_ci',
    ],
    'driver_can_register' => [
        'type' => 'tinyint(1)',
        'charset' => 'utf-8',
        'collation' => 'utf8_unicode_ci',
    ],
    'created_at' => [
        'type' => 'datetime',
    ],
    'updated_at' => [
        'type' => 'datetime',
    ],
];
