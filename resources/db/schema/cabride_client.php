<?php
/**
 *
 * Schema definition for 'cabride_client'
 *
 * Last update: 2018-10-26
 *
 */
$schemas = (!isset($schemas)) ? [] : $schemas;
$schemas['cabride_client'] = [
    'client_id' => [
        'type' => 'int(11) unsigned',
        'auto_increment' => true,
        'primary' => true,
    ],
    'customer_id' => [
        'type' => 'int(11) unsigned',
        'foreign_key' => [
            'table' => 'customer',
            'column' => 'customer_id',
            'name' => 'FK_CABRIDE_CID_CUSTOMER_CID',
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
];
