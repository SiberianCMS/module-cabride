<?php
/**
 *
 * Schema definition for 'cabride'
 *
 * Last update: 2020-09-01
 *
 */
$schemas = $schemas ?? [];
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
    'timezone' => [ // timezone
        'type' => 'varchar(128)',
        'charset' => 'utf8',
        'collation' => 'utf8_unicode_ci',
        'default' => 'UTC',
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
    'payment_gateways' => [
        'type' => 'text',
        'charset' => 'utf-8',
        'collation' => 'utf8_unicode_ci',
    ],
    'payout_period' => [
        'type' => 'varchar(64)',
        'charset' => 'utf-8',
        'collation' => 'utf8_unicode_ci',
        'default' => 'disabled',
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
    'enable_custom_form' => [
        'type' => 'tinyint(1)',
        'default' => '0',
    ],
    'show_passenger_photo' => [
        'type' => 'tinyint(1)',
        'default' => '1',
    ],
    'show_passenger_name' => [
        'type' => 'tinyint(1)',
        'default' => '1',
    ],
    'show_passenger_phone' => [
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
    'places_value_id' => [
        'type' => 'int(11) unsigned',
    ],
    'passenger_picture' => [
        'type' => 'varchar(1024)',
        'charset' => 'utf-8',
        'collation' => 'utf8_unicode_ci',
        'null' => true,
    ],
    'driver_picture' => [
        'type' => 'varchar(1024)',
        'charset' => 'utf-8',
        'collation' => 'utf8_unicode_ci',
        'null' => true,
    ],
    'nav_background' => [
        'type' => 'varchar(1024)',
        'charset' => 'utf-8',
        'collation' => 'utf8_unicode_ci',
        'null' => true,
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
    'enable_seats' => [
        'type' => 'tinyint(1)',
        'default' => '0',
    ],
    'seats_default' => [
        'type' => 'int(11)',
        'default' => '1',
    ],
    'enable_tour' => [
        'type' => 'tinyint(1)',
        'default' => '0',
    ],
    'custom_icon' => [ // currency
        'type' => 'mediumtext',
        'charset' => 'utf8',
        'collation' => 'utf8_unicode_ci',
    ],
    'created_at' => [
        'type' => 'datetime',
    ],
    'updated_at' => [
        'type' => 'datetime',
    ],
];
