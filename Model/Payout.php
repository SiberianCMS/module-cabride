<?php

/**
 * Class Cabride_Model_Payout
 */
class Cabride_Model_Payout extends Core_Model_Default
{
    /**
     * Cabride_Model_Payout constructor.
     * @param array $params
     * @throws Zend_Exception
     */
    public function __construct($params = [])
    {
        parent::__construct($params);
        $this->_db_table = 'Cabride_Model_Db_Table_Payout';
        return $this;
    }
}