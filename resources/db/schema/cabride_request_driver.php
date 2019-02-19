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
    'created_at' => [
        'type' => 'datetime',
    ],
];
