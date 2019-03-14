<?php

namespace Cabride\Model;

use Core\Model\Base;
use Push_Model_Message as Message;

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

    /**
     * @param $title
     * @param $text
     * @param $requestId
     * @param $target
     * @param $status
     * @param $actionValue
     * @param $valueId
     * @param $appId
     * @throws \Zend_Exception
     */
    public function sendMessage($title, $text, $requestId, $target, $status, $actionValue = null, $valueId = null, $appId = null)
    {
        if ($valueId === null) {
            $valueId = Cabride::getCurrentValueId();
        }

        $device = $this->getDevice() == 1 ? "android" : "ios";

        // History
        $logPush = new Push();
        $logPush
            ->setValueId($valueId)
            ->setPushDeviceId($this->getId())
            ->setRequestId($requestId)
            ->setTarget($target)
            ->setStatus($status)
            ->setTitle($title)
            ->setMessage($text)
            ->setActionValue($actionValue)
            ->save();

        $message = new Message();
        $message
            ->setIsStandalone(true)
            ->setToken($this->getToken())
            ->setTitle($title)
            ->setText($text)
            ->setSendToAll(false)
            ->setActionValue($actionValue)
            ->setForceAppRoute(true)
            ->setBase64(false);

        (new Push())->sendPush($device, $message, $appId);
    }
}