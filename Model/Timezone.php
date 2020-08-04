<?php

namespace Cabride\Model;

use DateTimeZone;

/**
 * Class Timezone
 * @package Cabride\Model
 */
class Timezone
{

    /**
     * cache version
     *
     * @var array
     */
    static public $compiled = null;

    /**
     * @return array
     */
    static public function getTranslated()
    {
        if (self::$compiled == null) {
            $timezones = DateTimeZone::listIdentifiers();
            $select = [];
            foreach ($timezones as $timezone) {
                $select[$timezone] = p__('timezone', $timezone);
            }

            asort($select);

            self::$compiled = $select;
        }

        return self::$compiled;
    }
}
