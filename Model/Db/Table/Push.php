<?php

namespace Cabride\Model\Db\Table;

use Core_Model_Db_Table;

/**
 * Class Push
 * @package Cabride\Model\Db\Table
 */
class Push extends Core_Model_Db_Table
{
    /**
     * @var string
     */
    protected $_name = "cabride_push";

    /**
     * @var string
     */
    protected $_primary = "push_id";
}
