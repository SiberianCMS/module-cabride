<?php

/**
 * Class Cabride_Model_Request
 */
class Cabride_Model_Request extends Core_Model_Default
{
    /**
     * Cabride_Model_Request constructor.
     * @param array $params
     * @throws Zend_Exception
     */
    public function __construct($params = [])
    {
        parent::__construct($params);
        $this->_db_table = 'Cabride_Model_Db_Table_Request';
        return $this;
    }
}