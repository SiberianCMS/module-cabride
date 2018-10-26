<?php
/**
 *
 * Schema definition for 'cabride_payment'
 *
 * Last update: 2018-10-26
 *
 */
$schemas = (!isset($schemas)) ? [] : $schemas;
$schemas['cabride_payment'] = [
    'payment_id' => [
        'type' => 'int(11) unsigned',
        'auto_increment' => true,
        'primary' => true,
    ],
    'amount' => [
        'type' => 'float',
    ],
    'method' => [
        'type' => 'float',
    ],
    'status' => [
        'type' => 'varchar(128)',
    ],
    'created_at' => [
        'type' => 'datetime',
    ],
    'updated_at' => [
        'type' => 'datetime',
    ],
];
