<?php
/**
 *
 * Schema definition for 'cabride_payment'
 *
 * Last update: 2018-10-26
 *
 */
$schemas = (!isset($schemas)) ? [] : $schemas;
$schemas['cabride_payment'] = [
    'payment_id' => [
        'type' => 'int(11) unsigned',
        'auto_increment' => true,
        'primary' => true,
    ],
    'value_id' => [
        'type' => 'int(11) unsigned',
        'foreign_key' => [
            'table' => 'application_option_value',
            'column' => 'value_id',
            'name' => 'FK_CABRIDE_PAYMENT_VID_AOV_VID',
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
    'client_vault_id' => [
        'type' => 'int(11) unsigned',
        'null' => true,
    ],
    'request_id' => [
        'type' => 'int(11) unsigned',
        'null' => true,
    ],
    'driver_id' => [
        'type' => 'int(11) unsigned',
        'null' => true,
    ],
    'amount' => [
        'type' => 'float',
    ],
    'amount_charged' => [
        'type' => 'float',
    ],
    'method' => [
        'type' => 'varchar(128)',
        'charset' => 'utf-8',
        'collation' => 'utf8_unicode_ci',
        'null' => true,
    ],
    'provider' => [
        'type' => 'varchar(128)',
        'charset' => 'utf-8',
        'collation' => 'utf8_unicode_ci',
        'null' => true,
    ],
    'status' => [
        'type' => 'varchar(128)',
        'charset' => 'utf-8',
        'collation' => 'utf8_unicode_ci',
    ],
    'payout_status' => [
        'type' => 'varchar(128)',
        'charset' => 'utf-8',
        'collation' => 'utf8_unicode_ci',
        'default' => 'idle',
    ],
    'return_status' => [
        'type' => 'varchar(128)',
        'charset' => 'utf-8',
        'collation' => 'utf8_unicode_ci',
        'default' => 'idle',
    ],
    'created_at' => [
        'type' => 'datetime',
    ],
    'updated_at' => [
        'type' => 'datetime',
    ],
];
