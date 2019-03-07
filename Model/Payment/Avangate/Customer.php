<?php

namespace Cabride\Model\Payment\Avangate;

use Customer_Model_Customer as SiberianCustomer;
use Siberian\Json;
use Cabride\Model\Utils;

/**
 * Class Customer
 * @package Cabride\Model\Payment\Avangate
 */
class Customer extends ClientAbstract
{
    /**
     * @param $customer
     * @return array|mixed
     * @throws \Exception
     */
    public function createCustomer($customer)
    {
        $headers = [
            "body" => Json::encode($customer)
        ];

        try {
            $rawResponse = $this->client->post("customers/", $headers);
            $customer = Json::decode($rawResponse->getBody()->getContents());
            
            return $customer;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $customerId
     * @return array|mixed
     * @throws \Exception
     */
    public function retrieveCustomer($customerId)
    {
        try {
            $rawResponse = $this->client->get("customers/{$customerId}/");
            $customer = Json::decode($rawResponse->getBody()->getContents());

            return $customer;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Example order payload!
     */
    public function customerPayload()
    {
        $payload = [
            "ExternalCustomerReference" => "",
            "FirstName" => "",
            "LastName" => "",
            //"CompanyName" => "", // opt
            //"FiscalCode" => "", // opt
            //"MachineId" => "", // opt
            "Address1" => "",
            // "Address2" => "", // opt
            "City" => "",
            "State" => "",
            "Zip" => "",
            "CountryCode" => "",
            "Phone" => "",
            "Email" => "",
            "Status" => "ACTIVE",
        ];

        return $payload;
    }

    /**
     * @param $client
     * @return array
     */
    public function clientToPayload ($client)
    {
        $customer = (new SiberianCustomer())
            ->find($client->getCustomerId(), "customer_id");

        $basePayload = $this->customerPayload();

        // Client cabride address parts
        $addressParts = Json::decode($client->getAddressParts());

        $basePayload["ExternalCustomerReference"] = "cabride-{$client->getId()}";
        $basePayload["FirstName"] = $customer->getFirstname();
        $basePayload["LastName"] = $customer->getLastname();
        $basePayload["Address1"] = $addressParts["street_number"] . " " . $addressParts["route"];
        $basePayload["City"] = $addressParts["locality"];
        $basePayload["State"] = $addressParts["administrative_area_level_2"];
        $basePayload["Zip"] = $addressParts["postal_code"];
        $basePayload["CountryCode"] = Utils::getCodeForCountry($addressParts["country"]);
        $basePayload["Phone"] = $client->getMobile();
        $basePayload["Email"] = $customer->getEmail();

        return $basePayload;
    }
}