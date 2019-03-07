<?php

namespace Cabride\Model\Payment\Avangate;

use Siberian\Json;
use Customer_Model_Customer as SiberianCustomer;
use Cabride\Model\Utils;

/**
 * Class Product
 * @package Cabride\Model\Payment\Avangate
 */
class Product extends ClientAbstract
{
    /**
     * @param $orderPayload
     * @return array|mixed
     * @throws \Exception
     */
    public function saveCreditCard($orderPayload)
    {
        $headers = [
            "body" => Json::encode($orderPayload),
        ];

        // To save a credit card, we use a free order to transmit credit-card information to 2checkout!
        try {
            $rawResponse = $this->client->post("orders/", $headers);
            $creditCard = Json::decode($rawResponse->getBody()->getContents());

            return $creditCard;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Example order payload!
     */
    public function createVaultProduct()
    {
        $payload = [
            "Currency" => "",
            "Country" => "",
            "Language" => "",
            //"ExternalReference" => "", // opt
            //"Source" => "", // opt
            //"MachineId" => "", // opt
            "CustomerReference" => "", // opt
            "Items" => [
                [
                    //"Code" => null, // opt
                    "Quantity" => "",
                    "isDynamic" => "",
                    "Tangible" => "",
                    "PurchaseType" => "",
                    "Price" => [
                        "Amount" => "",
                        "Type" => "",
                    ],
                    "AdditionalFields" => [
                        [
                            "Code" => "",
                            "Text" => "",
                            "Value" => "",
                        ]
                    ],
                ]
            ],
            "BillingDetails" => [
                "FirstName" => "",
                "LastName" => "",
                "CountryCode" => "",
                "State" => "",
                "City" => "",
                "Address1" => "",
                "Zip" => "",
                "Email" => "",
                "Phone" => "", // opt
                //"Company" => "", // opt
            ],
            "PaymentDetails" => [
                "Type" => "CC",
                "Currency" => "",
                "CustomerIP" => "",
            ],
            "PaymentMethod" => [
                "CardPayment" => [
                    "CardNumber" => "",
                    "CardType" => "",
                    "ExpirationYear" => "",
                    "ExpirationMonth" => "",
                    "HolderName" => "",
                    "CCID" => "",
                ]
            ],
        ];
    }

    public function clientAndCardToOrder($client, $card)
    {
        $customer = (new SiberianCustomer())
            ->find($client->getCustomerId(), "customer_id");


        // Client cabride address parts
        $addressParts = Json::decode($client->getAddressParts());

        $payload = [
            "Currency" => "EUR", // DEBUG
            "Country" => "FR", // DEBUG
            "Language" => "fr", // DEBUG
            //"ExternalReference" => "", // opt
            //"Source" => "", // opt
            //"MachineId" => "", // opt
            "CustomerReference" => $client->getTwocheckoutCustomerToken(),
            "Items" => [
                [
                    "Code" => null,
                    "Quantity" => "1",
                    "isDynamic" => true,
                    "Tangible" => false,
                    "PurchaseType" => "PRODUCT",
                    "Price" => [
                        "Amount" => "0",
                        "Type" => "CUSTOM",
                    ],
                ]
            ],
            "BillingDetails" => [
                "FirstName" => $customer->getFirstname(),
                "LastName" => $customer->getLastname(),
                "CountryCode" => Utils::getCodeForCountry($addressParts["country"]),
                "State" => $addressParts["administrative_area_level_2"],
                "City" => $addressParts["locality"],
                "Address1" => $addressParts["street_number"] . " " . $addressParts["route"],
                "Zip" => $addressParts["postal_code"],
                "Email" => $customer->getEmail(),
                "Phone" => $client->getMobile(), // opt
                //"Company" => "", // opt
            ],
            "PaymentDetails" => [
                "Type" => "CC",
                "Currency" => "EUR",
                "CustomerIP" => $_REQUEST["REMOTE_ADDR"],
            ],
            "PaymentMethod" => [
                "CardPayment" => [
                    "CardNumber" => $card["cardNumber"],
                    "CardType" => $card["cardType"],
                    "ExpirationYear" => $card["expiryYear"],
                    "ExpirationMonth" => $card["expiryMonth"],
                    "HolderName" => "",
                    "CCID" => $card["cvc"],
                ]
            ],
        ];

        return $payload;
    }
}
