<?php

use Cabride\Model\PushDevice;
use Cabride\Model\Cabride;
use Cabride\Model\Client;
use Cabride\Model\Driver;
use Cabride\Model\Stripe\Currency;
use Siberian\Exception;
use Core\Model\Base;
use Cabride\Controller\Mobile as MobileController;

/**
 * Class Cabride_Mobile_ViewController
 */
class Cabride_Mobile_ViewController extends MobileController
{
    /**
     *
     */
    public function fetchSettingsAction()
    {
        try {
            // Fetch installation config file!
            $configFile = Core_Model_Directory::getBasePathTo(
                "/app/local/modules/Cabride/resources/server/config.json"
            );

            if (!file_exists($configFile)) {
                throw new \Siberian\Exception(__("The configuration files is missing!"));
            }

            $config = \Siberian_Json::decode(file_get_contents($configFile));
            $wssUrl = $config["wssHost"] . ":" . $config["port"] . "/cabride";

            // DB Config!
            $optionValue = $this->getCurrentOptionValue();
            $valueId = $optionValue->getId();
            $dbConfig = (new Cabride())
                ->find($valueId, "value_id");

            $currency = Currency::getCurrency($dbConfig->getCurrency());

            $navBackground = $dbConfig->getNavBackground();
            if (!empty($navBackground)) {
                $navBackground = "/images/application{$navBackground}";
            }
            $driverPicture = $dbConfig->getDriverPicture();
            if (!empty($driverPicture)) {
                $driverPicture = "/images/application{$driverPicture}";
            }
            $passengerPicture = $dbConfig->getPassengerPicture();
            if (!empty($passengerPicture)) {
                $passengerPicture = "/images/application{$passengerPicture}";
            }

            $payload = [
                "success" => true,
                "settings" => [
                    "wssUrl" => $wssUrl,
                    "pageTitle" => (string) $optionValue->getTabbarName(),
                    "distanceUnit" => (string) $dbConfig->getDistanceUnit(),
                    "searchTimeout" => (integer) $dbConfig->getSearchTimeout(),
                    "searchRadius" => (integer) $dbConfig->getSearchRadius(),
                    "acceptedPayments" => (string) $dbConfig->getAcceptedPayments(),
                    "courseMode" => (string) $dbConfig->getCourseMode(),
                    "pricingMode" => (string) $dbConfig->getPricingMode(),
                    "paymentProvider" => (string) $dbConfig->getPaymentProvider(),
                    "stripePublicKey" => (string) $dbConfig->getStripePublicKey(),
                    "stripeIsSandbox" => (boolean) $dbConfig->getStripeIsSandbox(),
                    "driverCanRegister" => (boolean) $dbConfig->getDriverCanRegister(),
                    "defaultLat" => (float) $dbConfig->getDefaultLat(),
                    "defaultLng" => (float) $dbConfig->getDefaultLng(),
                    "currency" => $currency,
                    "commission_type" => (string) $dbConfig->getCommissionType(),
                    "commission" => (float) $dbConfig->getCommission(),
                    "commission_fmt" => Base::_formatPrice($dbConfig->getCommission(), $currency),
                    "commission_fixed" => (float) $dbConfig->getCommissionFixed(),
                    "commission_fixed_fmt" => Base::_formatPrice($dbConfig->getCommissionFixed(), $currency),
                    "passengerPicture" => $passengerPicture,
                    "driverPicture" => $driverPicture,
                    "navBackground" => $navBackground,
                    "showPassengerPhoto" => (boolean) $dbConfig->getShowPassengerPhoto(),
                    "showPassengerName" => (boolean) $dbConfig->getShowPassengerName(),
                    "showPassengerPhone" => (boolean) $dbConfig->getShowPassengerPhone(),
                ]
            ];

        } catch (\Exception $e) {
            $payload = [
                "error" => true,
                "message" => $e->getMessage()
            ];
        }

        $this->_sendJson($payload);
    }

    public function fetchUserAction ()
    {
        try {
            $request = $this->getRequest();
            $valueId = $this->getCurrentOptionValue()->getId();
            $customerId = $this->getSession()->getCustomerId();

            // First search in drivers!
            $driver = (new Driver())
                ->find([
                    "customer_id" => $customerId,
                    "value_id" => $valueId,
                ]);

            $user = null;
            if ($driver->getId()) {
                $user = [
                    "type" => "driver",
                    "driverId" => (integer) $driver->getId(),
                    "isOnline" => (boolean) $driver->getIsOnline(),
                ];
            } else {
                $passenger = (new Client())
                    ->find([
                        "customer_id" => $customerId,
                        "value_id" => $valueId,
                    ]);

                if ($passenger->getId()) {
                    // fetch saved cards if applies


                    $user = [
                        "clientId" => (integer) $passenger->getId(),
                        "type" => "passenger",
                    ];
                } else {
                    $user = [
                        "clientId" => (integer) $passenger->getId(),
                        "type" => "new",
                    ];
                }
            }

            $payload = [
                "success" => true,
                "user" => $user,
            ];
        } catch (\Exception $e) {
            $payload = [
                "error" => true,
                "message" => $e->getMessage(),
            ];
        }

        $this->_sendJson($payload);
    }

    /**
     *
     */
    public function updateUserAction ()
    {
        try {
            $request = $this->getRequest();
            $valueId = $this->getCurrentOptionValue()->getId();
            $customerId = $this->getSession()->getCustomerId();
            $userType = $request->getParam("userType", "passenger");

            switch ($userType) {
                case "passenger":
                    $passenger = (new Client())
                        ->find([
                            "customer_id" => $customerId,
                            "value_id" => $valueId,
                        ]);

                    if (!$passenger->getId()) {
                        $passenger
                            ->setCustomerId($customerId)
                            ->setValueId($valueId)
                            ->save();
                    }
                    break;
                case "driver":
                    $driver = (new Driver())
                        ->find([
                            "customer_id" => $customerId,
                            "value_id" => $valueId,
                        ]);

                    if (!$driver->getId()) {
                        $driver
                            ->setCustomerId($customerId)
                            ->setValueId($valueId)
                            ->setStatus("active") // @todo adapt to settings
                            ->save();
                    }
                    $driver
                        ->setStatus("active") // @todo adapt to settings
                        ->save();

                    break;
            }

            $payload = [
                "success" => true,
                "message" => __("Success"),
            ];
        } catch (\Exception $e) {
            $payload = [
                "error" => true,
                "message" => $e->getMessage(),
            ];
        }

        $this->_sendJson($payload);
    }

    /**
     *
     */
    public function updateUserPushAction ()
    {
        try {
            $request = $this->getRequest();
            $valueId = Cabride::getCurrentValueId();
            $customerId = $this->getSession()->getCustomerId();
            $device = $request->getParam("device", null);
            $token = $request->getParam("token", null);

            if (empty($customerId) || empty($device) || empty($token)) {
                throw new Siberian\Exception(__("A value is missing."));
            }

            $pushDevice = (new PushDevice())
                ->find($customerId, "customer_id");

            $pushDevice
                ->setCustomerId($customerId)
                ->setValueId($valueId)
                ->setDevice($device)
                ->setToken($token)
                ->save();

            $payload = [
                "success" => true,
                "message" => __("Success"),
            ];
        } catch (\Exception $e) {
            $payload = [
                "error" => true,
                "message" => $e->getMessage(),
            ];
        }

        $this->_sendJson($payload);
    }

    /**
     *
     */
    public function toggleOnlineAction ()
    {
        try {
            $request = $this->getRequest();
            $valueId = $this->getCurrentOptionValue()->getId();
            $customerId = $this->getSession()->getCustomerId();
            $isOnline = filter_var($request->getParam("isOnline", null), FILTER_VALIDATE_BOOLEAN);

            $driver = (new Driver())
                ->find([
                    "customer_id" => $customerId,
                    "value_id" => $valueId,
                ]);

            if (!$driver->getId()) {
                throw new Exception(p__("cabride",
                    "You are not registered as a driver! Please contact the App owner."));
            }

            $profileErrors = $driver->getProfileErrors();
            if ($isOnline && sizeof($profileErrors) > 0) {
                foreach ($profileErrors as &$profileError) {
                    $profileError = "- {$profileError}";
                }
                throw new Exception(p__("cabride", join("<br />", $profileErrors)));
            }

            $driver
                ->setIsOnline($isOnline)
                ->save();

            $payload = [
                "success" => true,
                "isOnline" => (boolean) $isOnline,
                "message" => __("Success"),
            ];
        } catch (\Exception $e) {
            $payload = [
                "error" => true,
                "message" => $e->getMessage(),
            ];
        }

        $this->_sendJson($payload);
    }
}
