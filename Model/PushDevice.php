<?php

namespace Cabride\Model;

use Core\Model\Base;
use Push2\Model\Onesignal\Message;
use Push2\Model\Onesignal\Player;
use Push2\Model\Onesignal\Scheduler;
use Siberian\Hook;

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

        Hook::listen(
            'push2.message.parsed',
            'cabride.alter.push2',
            static function ($payload) use ($requestId) {
                /**
                 * @var $notification \onesignal\client\model\Notification
                 */
                $notification = $payload['notification'];

                $notification->setContentAvailable(true);
                $notification->setData($notification->getData() + [
                        'cabride' => true,
                        'requestId' => $requestId,
                    ]);

                $payload['notification'] = $notification;

                return $payload;
            });

        $application = (new \Application_Model_Application())->find($appId);

        $messageValues = [
            'title' => $title,
            'body' => $text,
            'action_value' => $actionValue,
            'value_id' => $valueId,
            'app_id' => $appId,
        ];

        $scheduler = new Scheduler($application);
        $scheduler->buildMessageFromValues($messageValues);
        $scheduler->sendToCustomer($this->getCustomerId());

    }
}
