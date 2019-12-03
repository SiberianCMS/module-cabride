<?php

namespace Cabride\Controller;

use Application_Controller_Mobile_Default;

/**
 * Class Base
 * @package Cabride\Controller
 */
class Mobile extends Application_Controller_Mobile_Default
{
    /**
     * @return $this|Application_Controller_Mobile_Default|\Core_Controller_Default|void
     * @throws \Siberian\Exception
     * @throws \Zend_Exception
     */
    public function init(): self
    {
        parent::init();

        return $this;
    }
}
