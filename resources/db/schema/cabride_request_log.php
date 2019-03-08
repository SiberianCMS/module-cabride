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
    'created_at' => [
        'type' => 'datetime',
    ],
];
