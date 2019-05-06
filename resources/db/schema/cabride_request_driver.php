<?php
/**
 *
 * Schema definition for 'cabride_request_driver'
 *
 * Last update: 2018-10-26
 *
 */
$schemas = (!isset($schemas)) ? [] : $schemas;
$schemas['cabride_request_driver'] = [
    'request_driver_id' => [
        'type' => 'int(11) unsigned',
        'auto_increment' => true,
        'primary' => true,
    ],
    'request_id' => [
        'type' => 'int(11) unsigned',
    ],
    'driver_id' => [
        'type' => 'int(11) unsigned',
    ],
    'raw_route' => [
        'type' => 'longtext',
        'charset' => 'utf-8',
        'collation' => 'utf8_unicode_ci',
    ],
    'eta_to_client' => [
        'type' => 'int(11) unsigned',
        'null' => true,
    ],
    'eta_to_destination' => [
        'type' => 'int(11) unsigned',
        'null' => true,
    ],
    'time_to_client' => [
        'type' => 'int(11) unsigned',
        'null' => true,
    ],
    'time_to_destination' => [
        'type' => 'int(11) unsigned',
        'null' => true,
    ],
    'status' => [ // "pending", "accepted", "accepted_other", "onway", "inprogress", "declined", "done", "aborted", "expired"
        'type' => 'varchar(128)',
        'charset' => 'utf-8',
        'collation' => 'utf8_unicode_ci',
    ],
    'requested_at' => [
        'type' => 'int(11) unsigned',
    ],
    'expires_at' => [
        'type' => 'int(11) unsigned',
    ],
    'created_at' => [
        'type' => 'datetime',
    ],
];
