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
    ],
    'driver_id' => [
        'type' => 'int(11) unsigned',
    ],
    'vehicle_id' => [
        'type' => 'int(11) unsigned',
    ],
    'value_id' => [
        'type' => 'int(11) unsigned',
    ],
    'status' => [ // "pending", "accepted", "onway", "inprogress", "declined", "done", "aborted", "expired"
        'type' => 'varchar(128)',
        'charset' => 'utf-8',
        'collation' => 'utf8_unicode_ci',
    ],
    'static_image' => [
        'type' => 'varchar(256)',
        'charset' => 'utf-8',
        'collation' => 'utf8_unicode_ci',
    ],
    'estimated_cost' => [
        'type' => 'float',
    ],
    'cost' => [
        'type' => 'float',
    ],
    'distance' => [
        'type' => 'float',
    ],
    'duration' => [
        'type' => 'float',
    ],
    'from_address' => [
        'type' => 'text',
        'charset' => 'utf-8',
        'collation' => 'utf8_unicode_ci',
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
        'charset' => 'utf-8',
        'collation' => 'utf8_unicode_ci',
    ],
    'raw_route' => [
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
