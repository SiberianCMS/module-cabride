<?php

/**
 * Class Cabride_Model_Payment
 */
class Cabride_Model_Payment extends Core_Model_Default
{
    /**
     *  constructor.
     * @param array $params
     */
    public function __construct($params = [])
    {
        parent::__construct($params);
        $this->_db_table = 'Cabride_Model_Db_Table_Payment';
        return $this;
    }
}