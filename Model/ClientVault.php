<?php

namespace Cabride\Model;

use Core\Model\Base;

/**
 * Class ClientVault
 * @package Cabride\Model
 *
 * @method Db\Table\ClientVault getTable()
 */
class ClientVault extends Base
{
    /**
     * Client constructor.
     * @param array $params
     * @throws \Zend_Exception
     */
    public function __construct($params = [])
    {
        parent::__construct($params);
        $this->_db_table = 'Cabride\Model\Db\Table\ClientVault';
        return $this;
    }

    /**
     * @param $clientId
     * @return mixed
     */
    public function fetchForClientId($clientId)
    {
        return $this->getTable()->fetchForClientId($clientId);
    }

    /**
     * @return array|string
     */
    public function toJson($optionValue = null, $baseUrl = "")
    {
        $data = [
            "vaultId" => (integer) $this->getId(),
            "type" => (string) $this->getType(),
            "cardToken" => (string) $this->getCardToken(),
            "provider" => (string) $this->getPaymentProvider(),
            "brand" => (string) $this->getBrand(),
            "exp" => (string) $this->getExp(),
            "last" => (string) $this->getLast(),
            "isLastUsed" => (boolean) $this->getIsLastUsed(),
            "isFavorite" => (boolean) $this->getIsFavorite(),
            "isLast" => false,
        ];

        return $data;
    }
}