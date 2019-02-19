<?php

use Cabride\Model\Client;
use Cabride\Model\Request;

/**
 * Class Cabride_Mobile_RideController
 */
class Cabride_Mobile_RideController extends Application_Controller_Mobile_Default
{
    /**
     *
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
}
