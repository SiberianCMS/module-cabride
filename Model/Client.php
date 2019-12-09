<?php

namespace Cabride\Model;

use Core\Model\Base;
use Siberian\Json;

/**
 * Class Client
 * @package Cabride\Model
 *
 * @method integer getId()
 * @method Db\Table\Client getTable()
 */
class Client extends Base
{
    /**
     * Client constructor.
     * @param array $params
     * @throws \Zend_Exception
     */
    public function __construct($params = [])
    {
        parent::__construct($params);
        $this->_db_table = Db\Table\Client::class;
    }

    /**
     * @param $valueId
     * @return mixed
     */
    public function fetchForValueId($valueId)
    {
        return $this->getTable()->fetchForValueId($valueId);
    }

    /**
     * @return bool
     */
    public function hasInProgressRequest()
    {
        $result = $this->getTable()->hasInProgressRequest($this->getClientId());

        return (array_key_exists('total', $result) && $result['total'] > 0);
    }

    /**
     * @return $this
     * @throws \Zend_Exception
     */
    public function archiveStripCustomerToken()
    {
        $token = $this->getStripeCustomerToken();
        $archivedTokens = Json::decode($this->getTokenArchive());
        $archivedTokens[] = [
            'stripe_customer_token' => $token,
            'timestamp' => time()
        ];

        $this
            ->setTokenArchive(Json::encode($archivedTokens))
            ->save();

        // We also must archive old card vaults associated with this user!
        /**
         * @var $clientVaults ClientVault[]
         */
        $clientVaults = (new ClientVault())->findAll([
            'client_id = ?' => $this->getId()
        ]);

        foreach ($clientVaults as $clientVault) {
            $clientVault
                ->setIsRemoved(1)
                ->save();
        }

        return $this;
    }
}