<?php

namespace Cabride\Model\Db\Table;

use Core_Model_Db_Table;

/**
 * Class RequestLog
 * @package Cabride\Model\Db\Table
 */
class RequestLog extends Core_Model_Db_Table
{
    /**
     * @var string
     */
    protected $_name = "cabride_request_log";

    /**
     * @var string
     */
    protected $_primary = "request_log_id";
}
