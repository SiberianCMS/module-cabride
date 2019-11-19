<?php
/**
 *
 * Schema definition for "cabride_form_field"
 *
 * Last update: 2019-07-04
 *
 */
$schemas = (!isset($schemas)) ? [] : $schemas;
$schemas["cabride_form_field"] = [
    "field_id" => [
        "type" => "int(11) unsigned",
        "auto_increment" => true,
        "primary" => true,
    ],
    'value_id' => [
        'type' => 'int(11) unsigned',
        'foreign_key' => [
            'table' => 'application_option_value',
            'column' => 'value_id',
            'name' => 'FK_CABRIDE_FIELD_VID_AOV_VID',
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
    "label" => [
        "type" => "varchar(255)",
        "charset" => "utf8",
        "collation" => "utf8_unicode_ci",
    ],
    "field_type" => [
        "type" => "varchar(255)",
        "charset" => "utf8",
        "collation" => "utf8_unicode_ci",
    ],
    "number_min" => [
        "type" => "double",
    ],
    "number_max" => [
        "type" => "double",
    ],
    "number_step" => [
        "type" => "double",
    ],
    "date_format" => [
        "type" => "varchar(32)",
        "charset" => "utf8",
        "collation" => "utf8_unicode_ci",
    ],
    "datetime_format" => [
        "type" => "varchar(32)",
        "charset" => "utf8",
        "collation" => "utf8_unicode_ci",
    ],
    "is_required" => [
        "type" => "tinyint(1)",
        "default" => "0",
    ],
    "default_value" => [
        "type" => "text",
        "charset" => "utf8",
        "collation" => "utf8_unicode_ci",
    ],
    "position" => [
        "type" => "tinyint(1)",
        "default" => "0",
    ],
];