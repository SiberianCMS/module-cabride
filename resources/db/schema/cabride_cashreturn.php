<?php
/**
 *
 * Schema definition for 'cabride_cashreturn'
 *
 * Last update: 2018-10-26
 *
 */
$schemas = (!isset($schemas)) ? [] : $schemas;
$schemas['cabride_cashreturn'] = [
    'cashreturn_id' => [
        'type' => 'int(11) unsigned',
        'auto_increment' => true,
        'primary' => true,
    ],
    'driver_id' => [
        'type' => 'int(11) unsigned',
        'foreign_key' => [
            'table' => 'cabride_driver',
            'column' => 'driver_id',
            'name' => 'FK_CABRIDE_CRI_DRIVER_DID',
            'on_update' => 'CASCADE',
            'on_delete' => 'CASCADE',
        ],
        'index' => [
            'key_name' => 'driver_id',
            'index_type' => 'BTREE',
            'is_null' => false,
            'is_unique' => false,
        ],
    ],
    'value_id' => [
        'type' => 'int(11) unsigned',
        'foreign_key' => [
            'table' => 'application_option_value',
            'column' => 'value_id',
            'name' => 'FK_CABRIDE_CRI_VID_AOV_VID',
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
    'amount' => [
        'type' => 'float',
    ],
    'method' => [
        'type' => 'varchar(128)',
        'charset' => 'utf-8',
        'collation' => 'utf8_unicode_ci',
    ],
    'status' => [
        'type' => 'varchar(128)',
        'charset' => 'utf-8',
        'collation' => 'utf8_unicode_ci',
    ],
    'payment_ids' => [ // coma separated list of the concerned payment ids
        'type' => 'text',
        'charset' => 'utf-8',
        'collation' => 'utf8_unicode_ci',
    ],
    'period_from' => [
        'type' => 'datetime',
    ],
    'period_to' => [
        'type' => 'datetime',
    ],
    'return_date' => [
        'type' => 'datetime',
        'null' => true,
    ],
    'created_at' => [
        'type' => 'datetime',
    ],
    'updated_at' => [
        'type' => 'datetime',
    ],
];
