<?php
/**
 *
 * Schema definition for 'cabride_request'
 *
 * Last update: 2018-10-26
 *
 */
$schemas = (!isset($schemas)) ? [] : $schemas;
$schemas['cabride_request'] = [
    'request_id' => [
        'type' => 'int(11) unsigned',
        'auto_increment' => true,
        'primary' => true,
    ],
    'client_id' => [
        'type' => 'int(11) unsigned',
        'foreign_key' => [
            'table' => 'cabride_client',
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
    'status' => [
        'type' => 'varchar(128)',
    ],
    'cost' => [
        'type' => 'float',
    ],
    'from_address' => [
        'type' => 'text',
    ],
    'from_lat' => [
        'type' => 'float',
    ],
    'from_lng' => [
        'type' => 'float',
    ],
    'to_address' => [
        'type' => 'text',
    ],
    'to_lat' => [
        'type' => 'float',
    ],
    'to_lng' => [
        'type' => 'float',
    ],
    'request_mode' => [ // Immediate, Booked
        'type' => 'varchar(128)',
    ],
    'created_at' => [
        'type' => 'datetime',
    ],
    'updated_at' => [
        'type' => 'datetime',
    ],
];
