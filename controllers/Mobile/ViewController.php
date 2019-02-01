<?php

/**
 * Class Cabride_Mobile_ViewController
 */
class Cabride_Mobile_ViewController extends Application_Controller_Mobile_Default
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
            $valueId = $this->getCurrentOptionValue()->getId();
            $dbConfig = (new Cabride_Model_Cabride())
                ->find($valueId, "value_id");

            $payload = [
                "success" => true,
                "settings" => [
                    "wssUrl" => $wssUrl,
                    "distanceUnit" => (string) $dbConfig->getDistanceUnit(),
                    "searchTimeout" => (integer) $dbConfig->getSearchTimeout(),
                    "searchRadius" => (integer) $dbConfig->getSearchRadius(),
                    "acceptedPayments" => (string) $dbConfig->getAcceptedPayments(),
                    "courseMode" => (string) $dbConfig->getCourseMode(),
                    "pricingMode" => (string) $dbConfig->getPricingMode(),
                    "driverCanRegister" => (boolean) $dbConfig->getDriverCanRegister(),
                ]
            ];

        } catch (\Exception $e) {
            $payload = [
                "error" => true,
                "message" => __("An unknown error occurred, please try again later.")
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
            $driver = (new Cabride_Model_Driver())
                ->find([
                    "customer_id" => $customerId,
                    "value_id" => $valueId,
                ]);

            $user = null;
            if ($driver->getId()) {
                $user = [
                    "type" => "driver",
                ];
            } else {
                $passenger = (new Cabride_Model_Client())
                    ->find([
                        "customer_id" => $customerId,
                        "value_id" => $valueId,
                    ]);

                if ($passenger->getId()) {
                    $user = [
                        "type" => "passenger",
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
                    $passenger = (new Cabride_Model_Client())
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
                    $driver = (new Cabride_Model_Driver())
                        ->find([
                            "customer_id" => $customerId,
                            "value_id" => $valueId,
                        ]);

                    if (!$driver->getId()) {
                        $driver
                            ->setCustomerId($customerId)
                            ->setValueId($valueId)
                            ->save();
                    }
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
}
