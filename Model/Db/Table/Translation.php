<?php

namespace Cabride\Model\Db\Table;

use Core_Model_Db_Table;

/**
 * Class Translation
 * @package Cabride\Model\Db\Table
 */
class Translation extends Core_Model_Db_Table
{
    /**
     * @var string
     */
    protected $_name = 'cabride_translations';

    /**
     * @var string
     */
    protected $_primary = 'translation_id';

}
