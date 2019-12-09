<?php

namespace Cabride\Model;

use Core\Model\Base;

/**
 * Class Cashreturn
 * @package Cabride\Model
 *
 * @method integer getId()
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
        $this->_db_table = Db\Table\Cashreturn::class;
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