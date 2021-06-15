<?php

namespace Cabride\Model;

use Core\Model\Base;
use Siberian\Hook;
use Push_Model_Message as Message;

/**
 * Class PushDevice
 * @package Cabride\Model
 *
 * @method integer getId()
 * @method string getIcon()
 */
class PushDevice extends Base
{
    /**
     * @var string
     */
    protected $_db_table = Db\Table\PushDevice::class;

    /**
     * @param $title
     * @param $text
     * @param $requestId
     * @param $target
     * @param $status
     * @param null $actionValue
     * @param null $valueId
     * @param null $appId
     * @throws \Siberian\Exception
     * @throws \Zend_Exception
     */
    public function sendMessage($title, $text, $requestId, $target, $status, $actionValue = null, $valueId = null,
                                $appId = null)
    {
        if ($valueId === null) {
            $valueId = Cabride::getCurrentValueId();
        }

        $device = ((int) $this->getDevice()) === 1 ? 'android' : 'ios';

        // History
        $logPush = new Push();
        $logPush
            ->setValueId($valueId)
            ->setAppId($appId)
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
            ->setAppId($appId)
            ->setTitle($title)
            ->setText($text)
            ->setSendToAll(false)
            ->setActionValue($actionValue)
            ->setForceAppRoute(true)
            ->setBase64(false);

        Hook::listen(
            'push.message.android.parsed',
            'cabride.alter.android.push',
            static function ($payload) use ($requestId) {
                /**
                 * @var $msg \Siberian\Service\Push\CloudMessaging\Message
                 */
                $msg = $payload['message'];

                $cabride = [
                    'cabride' => true,
                    'requestId' => $requestId
                ];

                $msg->contentAvailable(true);
                $msg->addData('additional_payload', $cabride);

                $payload['message'] = $msg;

                return $payload;
            });

        Hook::listen(
            'push.message.ios.parsed',
            'cabride.alter.ios.push',
            static function ($payload) use ($requestId) {
                $msg = $payload['message'];

                $cabride = [
                    'cabride' => true,
                    'requestId' => $requestId
                ];

                // APS additional payload
                $msg->additional_payload = $cabride;

                $payload['message'] = $msg;

                return $payload;
            });

        (new Push())->sendPush($device, $message, $appId);
    }
}
