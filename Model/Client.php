<?php

/**
 * Class Cabride_Model_Client
 *
 * @method Cabride_Model_Db_Table_Client getTable()
 */
class Cabride_Model_Client extends Core_Model_Default
{
    /**
     * Cabride_Model_Client constructor.
     * @param array $params
     * @throws Zend_Exception
     */
    public function __construct($params = [])
    {
        parent::__construct($params);
        $this->_db_table = 'Cabride_Model_Db_Table_Client';
        return $this;
    }

    /**
     * @param $valueId
     * @return mixed
     */
    public function fetchForValueId($valueId)
    {
        return $this->getTable()->fetchForValueId($valueId);
    }
}