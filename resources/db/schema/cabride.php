<?php
/**
 *
 * Schema definition for 'cabride'
 *
 * Last update: 2018-10-26
 *
 */
$schemas = (!isset($schemas)) ? [] : $schemas;
$schemas['cabride'] = [
    'cabride_id' => [
        'type' => 'int(11) unsigned',
        'auto_increment' => true,
        'primary' => true,
    ],
    'value_id' => [
        'type' => 'int(11) unsigned',
        'foreign_key' => [
            'table' => 'application_option_value',
            'column' => 'value_id',
            'name' => 'FK_CABRIDE_VID_AOV_VID',
            'on_update' => 'CASCADE',
            'on_delete' => 'CASCADE',
        ],
        'index' => [
            'key_name' => 'value_id',
            'index_type' => 'BTREE',
            'is_null' => false,
            'is_unique' => false,
        ],
    ],
    'admin_emails' => [ // currency
        'type' => 'text',
        'charset' => 'utf8',
        'collation' => 'utf8_unicode_ci',
    ],
    'currency' => [ // currency
        'type' => 'varchar(10)',
        'charset' => 'utf8',
        'collation' => 'utf8_unicode_ci',
        'default' => 'USD',
    ],
    'distance_unit' => [ // km or miles
        'type' => 'varchar(16)',
        'charset' => 'utf8',
        'collation' => 'utf8_unicode_ci',
        'default' => 'km',
    ],
    'search_timeout' => [ // 60 seconds
        'type' => 'int',
        'default' => '60'
    ],
    'search_radius' => [
        'type' => 'float',
        'default' => '10'
    ],
    'accepted_payments' => [ // CB, Cash, All
        'type' => 'text',
        'charset' => 'utf-8',
        'collation' => 'utf8_unicode_ci',
    ],
    'payment_provider' => [ // stripe (only)
        'type' => 'varchar(64)',
        'charset' => 'utf-8',
        'collation' => 'utf8_unicode_ci',
    ],
    'commission_type' => [
        'type' => 'varchar(64)',
        'charset' => 'utf-8',
        'collation' => 'utf8_unicode_ci',
    ],
    'commission' => [
        'type' => 'varchar(256)',
        'charset' => 'utf-8',
        'collation' => 'utf8_unicode_ci',
    ],
    'commission_fixed' => [
        'type' => 'varchar(256)',
        'charset' => 'utf-8',
        'collation' => 'utf8_unicode_ci',
    ],
    'course_mode' => [ // immediate, all
        'type' => 'varchar(256)',
        'charset' => 'utf-8',
        'collation' => 'utf8_unicode_ci',
        'default' => 'immediate',
    ],
    'pricing_mode' => [ // fixed (by admin), driver
        'type' => 'varchar(256)',
        'charset' => 'utf-8',
        'collation' => 'utf8_unicode_ci',
        'default' => 'fixed',
    ],
    'driver_can_register' => [
        'type' => 'tinyint(1)',
        'default' => '1',
    ],
    'stripe_public_key' => [
        'type' => 'varchar(256)',
        'charset' => 'utf-8',
        'collation' => 'utf8_unicode_ci',
        'null' => true,
    ],
    'stripe_secret_key' => [
        'type' => 'varchar(256)',
        'charset' => 'utf-8',
        'collation' => 'utf8_unicode_ci',
        'null' => true,
    ],
    'stripe_is_sandbox' => [
        'type' => 'tinyint(1)',
        'default' => '0',
    ],
    'braintree_merchant_id' => [
        'type' => 'varchar(256)',
        'charset' => 'utf-8',
        'collation' => 'utf8_unicode_ci',
        'null' => true,
    ],
    'braintree_public_key' => [
        'type' => 'varchar(256)',
        'charset' => 'utf-8',
        'collation' => 'utf8_unicode_ci',
        'null' => true,
    ],
    'braintree_private_key' => [
        'type' => 'varchar(256)',
        'charset' => 'utf-8',
        'collation' => 'utf8_unicode_ci',
        'null' => true,
    ],
    'braintree_is_sandbox' => [
        'type' => 'tinyint(1)',
        'default' => '0',
    ],
    'center_map' => [
        'type' => 'varchar(1024)',
        'null' => true,
    ],
    'default_lat' => [
        'type' => 'float',
        'default' => '43.600000',
    ],
    'default_lng' => [
        'type' => 'float',
        'default' => '1.433333',
    ],
    'created_at' => [
        'type' => 'datetime',
    ],
    'updated_at' => [
        'type' => 'datetime',
    ],
];
