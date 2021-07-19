<?php

namespace Cabride\Model;

use Core\Model\Base;
use Push_Model_Certificate;
use Push_Model_Ios_Message;
use Push_Model_Android_Message;
use Push_Model_Firebase;
use Siberian_Service_Push_Apns;
use Siberian\Exception;
use Siberian\CloudMessaging\Sender\Fcm;
use Application_Model_Application as Application;
use Zend_Registry;

/**
 * Class Push
 * @package Cabride\Model
 *
 * @method integer getId()
 * @method string getIcon()
 */
class Push extends Base
{
    /**
     * Push constructor.
     * @param array $params
     * @throws \Zend_Exception
     */
    public function __construct($params = [])
    {
        parent::__construct($params);
        $this->_db_table = Db\Table\Push::class;
    }

    /**
     * @param $device
     * @param $message
     * @param $appId
     * @throws \Zend_Exception
     */
    public function sendPush($device, $message, $appId = null)
    {
        $logger = Zend_Registry::get("logger");
        if ($appId === null) {
            $application = self::getApplication();
        } else {
            $application = (new Application())->find($appId);
        }

        $appId = $application->getId();

        $iosCertificate = path(Push_Model_Certificate::getiOSCertificat($appId));

        if ($device === 'ios') {
            try {
                if (is_file($iosCertificate)) {
                    $instance = new Push_Model_Ios_Message(new Siberian_Service_Push_Apns($iosCertificate));
                    $instance->setMessage($message);
                    $instance->push();
                } else {
                    throw new Exception("You must provide an APNS Certificate for the App ID: {$appId}");
                }
            } catch (\Exception $e) {
                $logger->err(sprintf("[CabRide: %s]: " . $e->getMessage(), date("Y-m-d H:i:s")), "cabride_push");
            }
        } else if ($device === 'android') {
            try {
                $credentials = (new Push_Model_Firebase())
                    ->find('0', 'admin_id');

                $fcmKey = $credentials->getServerKey();
                $fcmInstance = null;
                if (!empty($fcmKey)) {
                    $fcmInstance = new Fcm($fcmKey);
                } else {
                    // Only FCM is mandatory by now!
                    throw new Exception("You must provide FCM Credentials");
                }

                $instance = new Push_Model_Android_Message($fcmInstance);
                $instance->setMessage($message);
                $instance->push();
            } catch (\Exception $e) {
                $logger->err(sprintf("[CabRide: %s]: " . $e->getMessage(), date("Y-m-d H:i:s")), "cabride_push");
            }
        }

    }
}
