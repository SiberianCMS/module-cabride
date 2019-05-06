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
     * @return $this|Application_Controller_Mobile_Default|void
     * @throws \Siberian\Exception
     * @throws \Zend_Exception
     */
    public function init()
    {
        parent::init();

        return $this;
    }
}
