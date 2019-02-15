<?php

namespace Cabride\Model;

use Core\Model\Base;

/**
 * Class PushDevice
 * @package Cabride\Model
 *
 * @method string getIcon()
 */
class PushDevice extends Base
{
    /**
     * PushDevice constructor.
     * @param array $params
     * @throws \Zend_Exception
     */
    public function __construct($params = [])
    {
        parent::__construct($params);
        $this->_db_table = "Cabride\Model\Db\Table\PushDevice";
        return $this;
    }
}