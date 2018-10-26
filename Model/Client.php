<?php

/**
 * Class Cabride_Model_Client
 */
class Cabride_Model_Client extends Core_Model_Default
{
    /**
     *  constructor.
     * @param array $params
     */
    public function __construct($params = [])
    {
        parent::__construct($params);
        $this->_db_table = 'Cabride_Model_Db_Table_Client';
        return $this;
    }
}