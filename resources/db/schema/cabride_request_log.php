<?php
/**
 *
 * Schema definition for 'cabride_request_log'
 *
 * Last update: 2018-10-26
 *
 */
$schemas = (!isset($schemas)) ? [] : $schemas;
$schemas['cabride_request_log'] = [
    'request_log_id' => [
        'type' => 'int(11) unsigned',
        'auto_increment' => true,
        'primary' => true,
    ],
    'request_id' => [
        'type' => 'int(11) unsigned',
    ],
    'value_id' => [
        'type' => 'int(11) unsigned',
    ],
    'status_from' => [ // "pending", "accepted", "declined", "done", "aborted", "expired"
        'type' => 'varchar(128)',
        'charset' => 'utf-8',
        'collation' => 'utf8_unicode_ci',
        'null' => true,
    ],
    'status_to' => [ // "pending", "accepted", "declined", "done", "aborted", "expired"
        'type' => 'varchar(128)',
        'charset' => 'utf-8',
        'collation' => 'utf8_unicode_ci',
        'null' => true,
    ],
    'source' => [ // "client", "driver", "cron", "admin"
        'type' => 'varchar(128)',
    ],
    'segment_hour' => [
        'type' => 'varchar(20)',
        'index' => [
            'key_name' => 'segment_hour',
            'index_type' => 'BTREE',
            'is_null' => false,
            'is_unique' => false,
        ],
    ],
    'segment_minute' => [
        'type' => 'varchar(20)',
        'index' => [
            'key_name' => 'segment_minute',
            'index_type' => 'BTREE',
            'is_null' => false,
            'is_unique' => false,
        ],
    ],
    'segment_day' => [
        'type' => 'varchar(20)',
        'index' => [
            'key_name' => 'segment_day',
            'index_type' => 'BTREE',
            'is_null' => false,
            'is_unique' => false,
        ],
    ],
    'segment_month' => [
        'type' => 'varchar(20)',
        'index' => [
            'key_name' => 'segment_month',
            'index_type' => 'BTREE',
            'is_null' => false,
            'is_unique' => false,
        ],
    ],
    'segment_year' => [
        'type' => 'varchar(20)',
        'index' => [
            'key_name' => 'segment_year',
            'index_type' => 'BTREE',
            'is_null' => false,
            'is_unique' => false,
        ],
    ],
    'created_at' => [
        'type' => 'datetime',
    ],
];
