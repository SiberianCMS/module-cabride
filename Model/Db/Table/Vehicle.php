<?php

namespace Cabride\Model\Db\Table;

use Core_Model_Db_Table;

/**
 * Class Vehicle
 * @package Cabride\Model\Db\Table
 */
class Vehicle extends Core_Model_Db_Table
{
    /**
     * @var string
     */
    protected $_name = "cabride_vehicle";

    /**
     * @var string
     */
    protected $_primary = "vehicle_id";
}
