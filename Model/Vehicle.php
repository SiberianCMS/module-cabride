<?php

namespace Cabride\Model;

use Core\Model\Base;

/**
 * Class Vehicle
 * @package Cabride\Model
 *
 * @method integer getId()
 * @method string getIcon()
 * @method float getBaseFare()
 * @method float getDistanceFare()
 * @method float getTimeFare()
 */
class Vehicle extends Base
{
    /**
     * Vehicle constructor.
     * @param array $params
     * @throws \Zend_Exception
     */
    public function __construct($params = [])
    {
        parent::__construct($params);
        $this->_db_table = Db\Table\Vehicle::class;
    }

    /**
     * @return string
     */
    public function getIconUri()
    {
        $icon = trim($this->getIcon());
        $iconPath = !empty($icon) ?
            '/images/application/' . $this->getIcon() :
            '/app/local/modules/Cabride/resources/design/desktop/flat/images/car-icon.png';

        return $iconPath;
    }
}