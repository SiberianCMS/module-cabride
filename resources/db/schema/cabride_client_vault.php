<?php
/**
 *
 * Schema definition for 'cabride_client'
 *
 * Last update: 2018-10-26
 *
 */
$schemas = (!isset($schemas)) ? [] : $schemas;
$schemas['cabride_client_vault'] = [
    'client_vault_id' => [
        'type' => 'int(11) unsigned',
        'auto_increment' => true,
        'primary' => true,
    ],
    'client_id' => [
        'type' => 'int(11) unsigned',
        'foreign_key' => [
            'table' => 'cabride_client',
            'column' => 'client_id',
            'name' => 'FK_CABRIDE_CVAULT_ID_CLIENT_CID',
            'on_update' => 'CASCADE',
            'on_delete' => 'CASCADE',
        ],
        'index' => [
            'key_name' => 'client_id',
            'index_type' => 'BTREE',
            'is_null' => false,
            'is_unique' => false,
        ],
    ],
    'type' => [ // "credit-card"
        'type' => 'varchar(20)',
        'null' => true,
    ],
    'payment_provider' => [ // "stripe", "twocheckout", "braintree"
        'type' => 'varchar(20)',
        'null' => true,
    ],
    'brand' => [
        'type' => 'varchar(32)',
        'null' => true,
    ],
    'exp' => [
        'type' => 'varchar(10)',
        'null' => true,
    ],
    'last' => [
        'type' => 'varchar(4)',
        'null' => true,
    ],
    'is_last_used' => [
        'type' => 'tinyint(1)',
        'default' => '0',
    ],
    'is_favorite' => [
        'type' => 'tinyint(1)',
        'default' => '0',
    ],
    'raw_payload' => [ // "stripe", "twocheckout", "braintree"
        'type' => 'longtext',
        'null' => true,
    ],
    'card_token' => [
        'type' => 'varchar(1024)',
        'null' => true,
    ],
    'is_removed' => [
        'type' => 'tinyint(1)',
        'default' => '0',
    ],
    'created_at' => [
        'type' => 'datetime',
    ],
    'updated_at' => [
        'type' => 'datetime',
    ],
];
