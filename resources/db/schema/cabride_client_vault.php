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
            'table' => 'client',
            'column' => 'client_id',
            'name' => 'FK_CABRIDE_CID_CLIENT_CID',
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
    'mobile' => [
        'type' => 'varchar(20)',
        'null' => true,
    ],
    'address' => [
        'type' => 'varchar(1024)',
        'null' => true,
    ],
];
