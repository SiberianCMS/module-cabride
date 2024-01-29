<?php

namespace Cabride\Model\Db\Table;

use Core_Model_Db_Table;

/**
 * Class Log
 * @package Cabride\Model\Db\Table
 */
class Log extends Core_Model_Db_Table
{
    /**
     * @var string
     */
    protected $_name = "cabride_log";

    /**
     * @var string
     */
    protected $_primary = "log_id";

}
