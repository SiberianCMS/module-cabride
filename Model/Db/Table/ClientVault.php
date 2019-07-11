<?php

namespace Cabride\Model\Db\Table;

use Core_Model_Db_Table;

/**
 * Class ClientVault
 * @package Cabride\Model\Db\Table
 */
class ClientVault extends Core_Model_Db_Table
{
    /**
     * @var string
     */
    protected $_name = "cabride_client_vault";

    /**
     * @var string
     */
    protected $_primary = "client_vault_id";

    /**
     * @param $clientId
     * @return mixed
     */
    public function fetchForClientId($clientId)
    {
        $select = $this->_db->select()
            ->from(["vault" => $this->_name], "*")
            ->where("vault.client_id = ?", $clientId)
            ->where("vault.is_removed = ?", 0)
            /** Show favorite first, then last used & then more recently added */
            ->order([
                "is_favorite DESC",
                "is_last_used DESC",
                "updated_at DESC",
            ]);

        return $this->toModelClass($this->_db->fetchAll($select));
    }
}
