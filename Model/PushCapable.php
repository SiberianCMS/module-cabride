<?php

namespace Cabride\Model;

use Core\Model\Base;
use Push2\Model\Onesignal\Scheduler;

/**
 * @method integer getCustomerId()
 */
abstract class PushCapable extends Base
{
    /**
     * @param $title
     * @param $text
     * @param $requestId
     * @param $target
     * @param $status
     * @param $actionValue
     * @param $valueId
     * @param $appId
     * @return void
     * @throws \Siberian\Exception
     * @throws \Zend_Exception
     * @throws \onesignal\client\ApiException
     */
    public function sendMessage($title, $text, $requestId, $target, $status, $actionValue = null,
                                $valueId = null, $appId = null)
    {
        if ($valueId === null) {
            $valueId = Cabride::getCurrentValueId();
        }

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