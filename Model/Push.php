<?php

namespace Cabride\Model;

use Core\Model\Base;

/**
 * Class Push
 * @package Cabride\Model
 *
 * @method string getIcon()
 */
class Push extends Base
{
    /**
     * Push constructor.
     * @param array $params
     * @throws \Zend_Exception
     */
    public function __construct($params = [])
    {
        parent::__construct($params);
        $this->_db_table = "Cabride\Model\Db\Table\Push";
        return $this;
    }
}