<?php

namespace Cabride\Model\Db\Table;

use Core_Model_Db_Table;

/**
 * Class Payment
 * @package Cabride\Model\Db\Table
 */
class Payment extends Core_Model_Db_Table
{
    /**
     * @var string
     */
    protected $_name = "cabride_payment";

    /**
     * @var string
     */
    protected $_primary = "payment_id";
}
