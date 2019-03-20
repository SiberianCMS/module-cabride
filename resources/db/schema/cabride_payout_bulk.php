<?php
/**
 *
 * Schema definition for 'cabride_payout_bulk'
 *
 * Last update: 2018-10-26
 *
 */
$schemas = (!isset($schemas)) ? [] : $schemas;
$schemas['cabride_payout_bulk'] = [
    'bulk_id' => [
        'type' => 'int(11) unsigned',
        'auto_increment' => true,
        'primary' => true,
    ],
    'value_id' => [
        'type' => 'int(11) unsigned',
        'foreign_key' => [
            'table' => 'application_option_value',
            'column' => 'value_id',
            'name' => 'FK_CABRIDE_PAYOUT_BULK_VID_AOV_VID',
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
    'total' => [
        'type' => 'float',
    ],
    'driver_ids' => [
        'type' => 'text',
        'charset' => 'utf-8',
        'collation' => 'utf8_unicode_ci',
    ],
    'payout_ids' => [ // coma separated list of the concerned payment ids
        'type' => 'text',
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
    'raw_csv' => [
        'type' => 'longtext',
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
