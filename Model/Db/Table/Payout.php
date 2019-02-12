<?php

namespace Cabride\Model\Db\Table;

use Core_Model_Db_Table;

/**
 * Class Payout
 * @package Cabride\Model\Db\Table
 */
class Payout extends Core_Model_Db_Table
{
    /**
     * @var string
     */
    protected $_name = "cabride_payout";

    /**
     * @var string
     */
    protected $_primary = "payout_id";
}
