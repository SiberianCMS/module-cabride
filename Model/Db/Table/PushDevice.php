<?php

namespace Cabride\Model\Db\Table;

use Core_Model_Db_Table;

/**
 * Class PushDevice
 * @package Cabride\Model\Db\Table
 */
class PushDevice extends Core_Model_Db_Table
{
    /**
     * @var string
     */
    protected $_name = "cabride_push_device";

    /**
     * @var string
     */
    protected $_primary = "push_device_id";
}
