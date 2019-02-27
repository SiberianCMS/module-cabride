<?php

namespace Cabride\Model;

use Core\Model\Base;
use Push_Model_Certificate;
use Push_Model_Ios_Message;
use Push_Model_Android_Message;
use Push_Model_Firebase;
use Siberian_Service_Push_Apns;
use Siberian\Exception;
use Siberian\CloudMessaging\Sender\Gcm;
use Siberian\CloudMessaging\Sender\Fcm;
use Zend_Registry;

/**
 * Class Push
 * @package Cabride\Model
 *
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
        $this->_db_table = "Cabride\Model\Db\Table\Push";
        return $this;
    }

    /**
     * @param $device
     * @param $message
     * @throws \Zend_Exception
     */
    public function sendPush($device, $message)
    {
        $logger = Zend_Registry::get("logger");
        $application = self::getApplication();
        $appId = $application->getId();

        $iosCertificate = path(Push_Model_Certificate::getiOSCertificat($appId));

        if ($device === "ios") {
            try {
                if (is_file($iosCertificate)) {
                    $instance = new Push_Model_Ios_Message(new Siberian_Service_Push_Apns(null, $iosCertificate));
                    $instance->setMessage($message);
                    $instance->push();
                } else {
                    throw new Exception("You must provide an APNS Certificate for the App ID: {$appId}");
                }
            } catch (\Exception $e) {
                $logger->err(sprintf("[CabRide: %s]: " . $e->getMessage(), date("Y-m-d H:i:s")), "cabride_push");
            }
        } else if ($device === "android") {
            try {
                $gcmKey = Push_Model_Certificate::getAndroidKey();
                $gcmInstance = null;
                if (!empty($gcmKey)) {
                    $gcmInstance = new Gcm($gcmKey);
                }

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

                if ($fcmInstance || $gcmInstance) {
                    $instance = new Push_Model_Android_Message($fcmInstance, $gcmInstance);
                    $instance->setMessage($message);
                    $instance->push();
                }
            } catch (\Exception $e) {
                $logger->err(sprintf("[CabRide: %s]: " . $e->getMessage(), date("Y-m-d H:i:s")), "cabride_push");
            }
        }

    }
}