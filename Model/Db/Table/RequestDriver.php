<?php

namespace Cabride\Model\Db\Table;

use Core_Model_Db_Table;

/**
 * Class RequestDriver
 * @package Cabride\Model\Db\Table
 */
class RequestDriver extends Core_Model_Db_Table
{
    /**
     * @var string
     */
    protected $_name = "cabride_request_driver";

    /**
     * @var string
     */
    protected $_primary = "request_driver_id";
}
