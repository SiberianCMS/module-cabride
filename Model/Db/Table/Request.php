<?php

namespace Cabride\Model\Db\Table;

use Core_Model_Db_Table;

/**
 * Class Request
 * @package Cabride\Model\Db\Table
 */
class Request extends Core_Model_Db_Table
{
    /**
     * @var string
     */
    protected $_name = "cabride_request";

    /**
     * @var string
     */
    protected $_primary = "request_id";
}
