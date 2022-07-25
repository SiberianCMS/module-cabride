<?php

namespace Cabride\Controller;

use Api_Controller_Default;
use Application_Model_Option_Value;
use Siberian\Json;
use Siberian\Exception;

/**
 * Class Base
 * @package Cabride\Controller
 */
class Base extends Api_Controller_Default
{

    /**
     * @var string
     */
    public $namespace = 'cabride';

    /**
     * @var []
     */
    public $user = null;

    /**
     * @var integer
     */
    public $userId = null;

    /**
     * @var null
     */
    public $localRequest = null;

    /**
     * @var []
     */
    public $params = [];

    /**
     * @var Application_Model_Option_Value
     */
    public $option = null;

    /**
     * @return $this
     * @throws \Zend_Session_Exception
     */
    public function init(): self
    {
        parent::init();

        $session = $this->getSession();

        $this->user = $this->typeUser($session->getCustomer()->getData());
        $this->userId = (integer)$session->getCustomerId();
        $this->localRequest = $this->getRequest();
        $this->params = Json::decode($this->localRequest->getRawBody());

        return $this;
    }

    /**
     * @param $user
     * @return array
     */
    private function typeUser($user): array
    {
        return [
            'app_id' => (integer) $user['app_id'],
            'customer_id' => (integer) $user['customer_id'],
            'can_access_locked_features' => (boolean) $user['can_access_locked_features'],
            'civility' => (string) $user['civility'],
            'created_at' => (string) $user['created_at'],
            'email' => (string) $user['email'],
            'firstname' => (string) $user['firstname'],
            'id' => (integer) $user['id'],
            'image' => (string) $user['image'],
            'is_active' => (boolean) $user['is_active'],
            'is_custom_image' => (boolean) $user['is_custom_image'],
            'lastname' => (string) $user['lastname'],
            'nickname' => (string) $user['nickname'],
            'password' => (string) $user['password'],
            'show_in_social_gaming' => (boolean) $user['show_in_social_gaming'],
            'updated_at' => (string) $user['updated_at'],
        ];
    }

    /**
     * @return $this|Application_Model_Option_Value|bool|null
     */
    protected function getCurrentOptionValue()
    {
        $optionValue = (new Application_Model_Option_Value())
            ->find($this->params['valueId']);

        return ($optionValue && $optionValue->getId()) ?
            $optionValue : false;
    }

    /**
     * @throws Exception
     */
    protected function belongsToApp()
    {
        $app = $this->getApplication();
        if ((integer)$app->getId() !== $this->user['app_id']) {
            if (empty($this->user['customer_id'])) {
                throw new Exception(__('User not logged in'));
            }
            throw new Exception(__('User does not belong to App'));
        }

        $this->option = $app->getOption('cabride');
        if (!$this->option->getId()) {
            throw new Exception(__('Unable to find cabride feature.'));
        }
    }
}
