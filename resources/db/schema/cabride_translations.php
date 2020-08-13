<?php
/**
 *
 * Schema definition for 'cabride_translations'
 *
 * Last update: 2020-08-05
 *
 */
$schemas = $schemas ?? [];
$schemas['cabride_translations'] = [
    'translation_id' => [
        'type' => 'int(11) unsigned',
        'auto_increment' => true,
        'primary' => true,
    ],
    'app_id' => [
        'type' => 'int(11) unsigned',
        'foreign_key' => [
            'table' => 'application',
            'column' => 'app_id',
            'name' => 'FK_CABRIDE_TRANSLATION_APPID',
            'on_update' => 'CASCADE',
            'on_delete' => 'CASCADE',
        ],
        'index' => [
            'key_name' => 'KEY_CABRIDE_TRANSLATION_APPID',
            'index_type' => 'BTREE',
            'is_null' => false,
            'is_unique' => false,
        ],
    ],
    'locale' => [
        'type' => 'varchar(16)',
        'charset' => 'utf8',
        'collation' => 'utf8_unicode_ci',
    ],
    'context' => [
        'type' => 'varchar(255)',
        'charset' => 'utf8',
        'collation' => 'utf8_unicode_ci',
    ],
    'original' => [
        'type' => 'text',
        'charset' => 'utf8',
        'collation' => 'utf8_unicode_ci',
    ],
    'translation' => [
        'type' => 'text',
        'charset' => 'utf8',
        'collation' => 'utf8_unicode_ci',
    ],
];
