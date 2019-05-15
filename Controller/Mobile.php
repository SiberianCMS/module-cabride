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
     * @return $this|void
     */
    public function init()
    {
        parent::init();

        return $this;
    }
}
