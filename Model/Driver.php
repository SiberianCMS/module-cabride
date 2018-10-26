<?php

/**
 * Class Cabride_Model_Driver
 */
class Cabride_Model_Driver extends Core_Model_Default
{
    /**
     *  constructor.
     * @param array $params
     */
    public function __construct($params = [])
    {
        parent::__construct($params);
        $this->_db_table = 'Cabride_Model_Db_Table_Driver';
        return $this;
    }
}