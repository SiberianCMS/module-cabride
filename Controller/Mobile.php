<?php

namespace Cabride\Controller;

use Application_Controller_Mobile_Default;
use Cabride\Model\Cabride;

/**
 * Class Base
 * @package Cabride\Controller
 */
class Mobile extends Application_Controller_Mobile_Default
{
    /**
     * @return $this
     * @throws \Zend_Exception
     */
    public function init()
    {
        parent::init();

        $optionValue = $this->getCurrentOptionValue();
        $valueId = $optionValue->getId();

        $cabride = (new Cabride())
            ->find($valueId, "value_id");

        // All controllers going through cabride will have it's timezone applied
        date_default_timezone_set($cabride->getTimezone());

        return $this;
    }
}
