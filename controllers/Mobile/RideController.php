<?php

use Cabride\Model\Client;
use Cabride\Model\Driver;
use Cabride\Model\Request;
use Cabride\Model\RequestDriver;
use Core\Model\Base;

/**
 * Class Cabride_Mobile_RideController
 */
class Cabride_Mobile_RideController extends Application_Controller_Mobile_Default
{
    /**
     * Client route
     */
    public function meAction()
    {
        try {
            $request = $this->getRequest();
            $session = $this->getSession();
            $customerId = $session->getCustomerId();
            $optionValue = $this->getCurrentOptionValue();
            $valueId = $optionValue->getId();

            $client = (new Client())->find($customerId, "customer_id");
            $rides = (new Request())->findExtended($valueId, $client->getId());

            $collection = [];
            foreach ($rides as $ride) {
                $data = $ride->getData();

                // Makes payload lighter!
                unset($data["raw_route"]);

                $data["formatted_price"] = Base::_formatPrice($data["estimated_cost"]);

                // Recast values
                $data["search_timeout"] = (integer) $data["search_timeout"];
                $data["timestamp"] = (integer) $data["timestamp"];

                $collection[] = $data;
            }

            $payload = [
                "success" => true,
                "collection" => $collection,
            ];
        } catch (\Exception $e) {
            $payload = [
                "error" => true,
                "message" => __("An unknown error occurred, please try again later."),
                "except" => $e->getMessage()
            ];
        }

        $this->_sendJson($payload);
    }

    /**
     * Driver route
     */
    public function pendingAction ()
    {
        try {
            $request = $this->getRequest();
            $session = $this->getSession();
            $customerId = $session->getCustomerId();
            $optionValue = $this->getCurrentOptionValue();
            $valueId = $optionValue->getId();

            $driver = (new Driver())->find($customerId, "customer_id");
            $rides = (new Request())->findForDriver($valueId, $driver->getId(), "pending");

            $collection = [];
            foreach ($rides as $ride) {
                $data = $ride->getData();

                // Makes payload lighter!
                unset($data["raw_route"]);

                $data["formatted_price"] = Base::_formatPrice($data["estimated_cost"]);

                // Recast values
                $data["search_timeout"] = (integer) $data["search_timeout"];
                $data["timestamp"] = (integer) $data["timestamp"];

                $collection[] = $data;
            }

            $payload = [
                "success" => true,
                "collection" => $collection,
            ];
        } catch (\Exception $e) {
            $payload = [
                "error" => true,
                "message" => __("An unknown error occurred, please try again later."),
                "except" => $e->getMessage()
            ];
        }

        $this->_sendJson($payload);
    }

    /**
     * Driver route
     */
    public function acceptedAction ()
    {
        try {
            $request = $this->getRequest();
            $session = $this->getSession();
            $customerId = $session->getCustomerId();
            $optionValue = $this->getCurrentOptionValue();
            $valueId = $optionValue->getId();

            $driver = (new Driver())->find($customerId, "customer_id");
            $rides = (new Request())->findForDriver($valueId, $driver->getId(), "accepted");

            $collection = [];
            foreach ($rides as $ride) {
                $data = $ride->getData();

                // Makes payload lighter!
                unset($data["raw_route"]);

                $data["formatted_price"] = Base::_formatPrice($data["estimated_cost"]);

                // Recast values
                $data["search_timeout"] = (integer) $data["search_timeout"];
                $data["timestamp"] = (integer) $data["timestamp"];

                $collection[] = $data;
            }

            $payload = [
                "success" => true,
                "collection" => $collection,
            ];
        } catch (\Exception $e) {
            $payload = [
                "error" => true,
                "message" => __("An unknown error occurred, please try again later."),
                "except" => $e->getMessage()
            ];
        }

        $this->_sendJson($payload);
    }

    /**
     * Driver route
     */
    public function completedAction ()
    {
        try {
            $request = $this->getRequest();
            $session = $this->getSession();
            $customerId = $session->getCustomerId();
            $optionValue = $this->getCurrentOptionValue();
            $valueId = $optionValue->getId();

            $driver = (new Driver())->find($customerId, "customer_id");
            $rides = (new Request())->findForDriver($valueId, $driver->getId(), "done");

            $collection = [];
            foreach ($rides as $ride) {
                $data = $ride->getData();

                // Makes payload lighter!
                unset($data["raw_route"]);

                $data["formatted_price"] = Base::_formatPrice($data["estimated_cost"]);

                // Recast values
                $data["search_timeout"] = (integer) $data["search_timeout"];
                $data["timestamp"] = (integer) $data["timestamp"];

                $collection[] = $data;
            }

            $payload = [
                "success" => true,
                "collection" => $collection,
            ];
        } catch (\Exception $e) {
            $payload = [
                "error" => true,
                "message" => __("An unknown error occurred, please try again later."),
                "except" => $e->getMessage()
            ];
        }

        $this->_sendJson($payload);
    }

    /**
     * Driver route
     */
    public function cancelledAction ()
    {
        try {
            $request = $this->getRequest();
            $session = $this->getSession();
            $customerId = $session->getCustomerId();
            $optionValue = $this->getCurrentOptionValue();
            $valueId = $optionValue->getId();

            $driver = (new Driver())->find($customerId, "customer_id");
            $rides = (new Request())->findForDriver($valueId, $driver->getId(), "declined");

            $collection = [];
            foreach ($rides as $ride) {
                $data = $ride->getData();

                // Makes payload lighter!
                unset($data["raw_route"]);

                $data["formatted_price"] = Base::_formatPrice($data["estimated_cost"]);

                // Recast values
                $data["search_timeout"] = (integer) $data["search_timeout"];
                $data["timestamp"] = (integer) $data["timestamp"];

                $collection[] = $data;
            }

            $payload = [
                "success" => true,
                "collection" => $collection,
            ];
        } catch (\Exception $e) {
            $payload = [
                "error" => true,
                "message" => __("An unknown error occurred, please try again later."),
                "except" => $e->getMessage()
            ];
        }

        $this->_sendJson($payload);
    }

    /**
     * Driver route
     */
    public function declineAction ()
    {
        try {
            $request = $this->getRequest();
            $session = $this->getSession();
            $customerId = $session->getCustomerId();
            $optionValue = $this->getCurrentOptionValue();
            $valueId = $optionValue->getId();
            $requestId = $request->getParam("requestId", false);
            $ride = (new Request())->find($requestId);

            if (!$requestId || !$ride->getId()) {
                throw new Exception(p__("cabride",
                    "Sorry, we are unable to find this ride request!"));
            }

            $driver = (new Driver())->find($customerId, "customer_id");

            $requestDriver = (new RequestDriver())->find([
                "request_id" => $requestId,
                "driver_id" => $driver->getId(),
                "status" => "pending"
            ]);

            if ($requestDriver->getId()) {
                $requestDriver
                    ->setStatus("declined")
                    ->save();
            }

            $payload = [
                "success" => true,
                "message" => p__("cabride", "You declined the request!"),
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
     * Driver route
     */
    public function acceptAction ()
    {
        try {
            $request = $this->getRequest();
            $session = $this->getSession();
            $customerId = $session->getCustomerId();
            $optionValue = $this->getCurrentOptionValue();
            $valueId = $optionValue->getId();
            $requestId = $request->getParam("requestId", false);
            $ride = (new Request())->find($requestId);

            if (!$requestId || !$ride->getId()) {
                throw new Exception(p__("cabride",
                    "Sorry, we are unable to find this ride request!"));
            }

            $driver = (new Driver())->find($customerId, "customer_id");

            $requestDriver = (new RequestDriver())->find([
                "request_id" => $requestId,
                "driver_id" => $driver->getId(),
                "status" => "declined"
            ]);

            if (!$requestDriver->getId()) {
                throw new Exception(p__("cabride",
                    "Sorry, we are unable to find this ride request!"));
            }

            $requestDriver
                ->setStatus("accepted")
                ->save();

            $ride->changeStatus("accepted", Request::SOURCE_DRIVER);

            // Notify the client his ride is accepted!
            $ride->notifyClient();

            // So also expires all other drivers!
            $requestDrivers = (new RequestDriver())
                ->findAll(["request_id = ?" => $requestId, "driver_id != ?" => $driver->getId()]);
            foreach ($requestDrivers as $requestDriver) {
                $requestDriver
                    ->setStatus("accepted_other")
                    ->save();
            }


            $payload = [
                "success" => true,
                "message" => p__("cabride", "You finally accepted the request!"),
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
