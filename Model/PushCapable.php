<?php

namespace Cabride\Model;

use Core\Model\Base;
use Push2\Model\Onesignal\Scheduler;
use Siberian\Mail;

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
        $cabride = (new Cabride())->find($valueId);

        // Should we send email too?
        if ($cabride->getEmailNotifications()) {
            $this->sendEmail($cabride, $title, $text);
        }

        // Should we send SMS too?
        if ($cabride->getSmsNotifications()) {
            $this->sendSms($cabride, $title, $text);
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

    public function sendEmail($cabride, $title, $text)
    {
        $customer = (new \Customer_Model_Customer())->find($this->getCustomerId());

        // Notify driver by e-mail
        try {
            $values = [
                'title' => $title,
                'more' => $text,
            ];

            // Fake recipient for the smtp-sender!
            $recipient = new \Admin_Model_Admin();
            $recipient
                ->setEmail($customer->getEmail()
                ->setFirstname($customer->getFirstname())
                ->setLastname($customer->getLastname()));

            // SMTP Mailer
            (new Mail())
                ->simpleEmail(
                    'cabride',
                    'ride_request',
                    $title,
                    [
                        $recipient
                    ],
                    $values
                )->send();
        } catch (\Exception $e) {
            // Unable to send e-mail
            file_put_contents(path("/var/log/cabride.log"), print_r($values, true), FILE_APPEND);
            file_put_contents(path("/var/log/cabride.log"), print_r($e->getMessage(), true), FILE_APPEND);
        }
    }

    public function sendSms($cabride, $title, $text)
    {
        $customer = (new \Customer_Model_Customer())->find($this->getCustomerId());

        // Notify driver by SMS
        try {

            // Fake recipient for the smtp-sender!
            $mobile = $customer->getMobile();

            // Send SMS with Twilio php sdk
            $twilio = new \Twilio\Rest\Client(
                $cabride->getTwilioSid(),
                $cabride->getTwilioToken()
            );

            $twilio->messages->create(
                $mobile->getMobile(),
                [
                    'from' => $cabride->getTwilioFrom(),
                    'body' => $title . "\n" . $text,
                ]
            );

        } catch (\Exception $e) {
            // Unable to send SMS
            file_put_contents(path("/var/log/cabride.log"), print_r($mobile, true), FILE_APPEND);
            file_put_contents(path("/var/log/cabride.log"), print_r($e->getMessage(), true), FILE_APPEND);
        }
    }
}