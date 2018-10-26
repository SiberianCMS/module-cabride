<?php

/**
 * Class Cabride_Model_Cabride
 */
class Cabride_Model_Cabride extends Core_Model_Default
{
    /**
     *  constructor.
     * @param array $params
     */
    public function __construct($params = [])
    {
        parent::__construct($params);
        $this->_db_table = 'Cabride_Model_Db_Table_Cabride';
        return $this;
    }
}