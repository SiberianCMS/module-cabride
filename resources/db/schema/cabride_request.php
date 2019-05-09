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
        'null' => true,
    ],
    'vehicle_id' => [
        'type' => 'int(11) unsigned',
    ],
    'value_id' => [
        'type' => 'int(11) unsigned',
    ],
    'client_vault_id' => [
        'type' => 'int(11) unsigned',
        'null' => true,
    ],
    'payment_type' => [ // "credit-card" (vault = client_vault_id), "cash"
        'type' => 'varchar(64)',
        'null' => true,
    ],
    'status' => [ // "pending", "accepted", "onway", "inprogress", "declined", "done", "aborted", "expired"
        'type' => 'varchar(128)',
        'charset' => 'utf-8',
        'collation' => 'utf8_unicode_ci',
        'null' => true,
    ],
    'static_image' => [
        'type' => 'varchar(256)',
        'charset' => 'utf-8',
        'collation' => 'utf8_unicode_ci',
    ],
    'estimated_cost' => [
        'type' => 'float',
    ],
    'estimated_lowest_cost' => [
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
    'driver_rating' => [
        'type' => 'tinyint(1)',
        'default' => '-1',
    ],
    'course_rating' => [
        'type' => 'tinyint(1)',
        'default' => '-1',
    ],
    'course_comment' => [
        'type' => 'text',
        'charset' => 'utf-8',
        'collation' => 'utf8_unicode_ci',
    ],
    'cancel_reason' => [
        'type' => 'varchar(128)',
        'charset' => 'utf-8',
        'collation' => 'utf8_unicode_ci',
    ],
    'cancel_note' => [
        'type' => 'text',
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
    'updated_at' => [
        'type' => 'datetime',
    ],
];
