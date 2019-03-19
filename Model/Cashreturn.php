<?php

namespace Cabride\Model;

use Core\Model\Base;

/**
 * Class Cashreturn
 * @package Cabride\Model
 */
class Cashreturn extends Base
{
    /**
     * Cashreturn constructor.
     * @param array $params
     * @throws \Zend_Exception
     */
    public function __construct($params = [])
    {
        parent::__construct($params);
        $this->_db_table = 'Cabride\Model\Db\Table\Cashreturn';
        return $this;
    }

    /**
     * @param $valueId
     * @return mixed
     */
    public function fetchArchives ($valueId)
    {
        return $this->getTable()->fetchArchives($valueId);
    }
}