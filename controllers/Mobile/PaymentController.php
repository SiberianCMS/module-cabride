<?php

use Cabride\Model\Client;
use Cabride\Model\ClientVault;
use Siberian\Exception;
use Siberian\Json;

/**
 * Class Cabride_Mobile_PaymentController
 */
class Cabride_Mobile_PaymentController extends Application_Controller_Mobile_Default
{
    /**
     *
     */
    public function saveCardAction()
    {
        /**
        Array
        (
            [card] => Array
            (
                [id] => tok_1EAGFwEinMwd9tymQ2n4TZhS
                [object] => token
                [card] => Array
                    (
                        [id] => card_1EAGFvEinMwd9tymssX81ggf
                        [object] => card
                        [address_city] =>
                        [address_country] =>
                        [address_line1] =>
                        [address_line1_check] =>
                        [address_line2] =>
                        [address_state] =>
                        [address_zip] => 31700
                        [address_zip_check] => unchecked
                        [brand] => Visa
                        [country] => US
                        [cvc_check] => unchecked
                        [dynamic_last4] =>
                        [exp_month] => 12
                        [exp_year] => 2021
                        [funding] => credit
                        [last4] => 4242
                        [metadata] => Array()
                        [name] =>
                        [tokenization_method] =>
                    )
                [client_ip] => 193.253.193.101
                [created] => 1551703856
                [livemode] =>
                [type] => card
                [used] =>
            )
            [type] => stripe
        )
         */
        try {
            $request = $this->getRequest();
            $data = $request->getBodyParams();
            $optionValue = $this->getCurrentOptionValue();
            $customerId = $this->getSession()->getCustomerId();

            $client = (new Client())->find($customerId, "customer_id");

            if (!$client->getId()) {
                throw new Exception(p__("cabride", "You session expired!"));
            }
            
            $type = $data["type"];
            $card = $data["card"];

            if (!in_array($type, ["stripe", "twocheckout", "braintree"])) {
                throw new Exception(p__("cabride", "This payment type is not allowed."));
            }

            // Search for a similar card!
            $similarVaults = (new ClientVault())->findAll([
                "exp = ?" => $card["card"]["exp_month"] . "/" . substr($card["card"]["exp_year"], 2),
                "last = ?" => $card["card"]["last4"],
                "brand = ?" => $card["card"]["brand"],
                "payment_provider = ?" => $type,
            ]);

            if ($similarVaults->count() > 0) {
                throw new Exception(p__("cabride", "Seems you already added this card! If there was an error please remove the existing card first, then add it again."));
            }

            $vault = null;
            switch ($type) {
                case "stripe":
                    $vault = new ClientVault();
                    $vault
                        ->setClientId($client->getId())
                        ->setType("credit-card")
                        ->setPaymentProvider("stripe")
                        ->setBrand($card["card"]["brand"])
                        ->setExp($card["card"]["exp_month"] . "/" . substr($card["card"]["exp_year"], 2))
                        ->setLast($card["card"]["last4"])
                        ->setCardToken($card["id"])
                        ->setRawPayload(Json::encode($card))
                        ->save();

                    break;
                case "twocheckout":

                    break;
                case "braintree":

                    break;
            }

            $clientVaults = (new ClientVault())->fetchForClientId($client->getId());
            $vaults = [];
            foreach ($clientVaults as $clientVault) {
                // Filter vaults by type!
                $data = $clientVault->toJson();
                if ($clientVault->getPaymentProvider() === $type) {
                    $vaults[] = $data;
                }
            }

            $payload = [
                "success" => true,
                "vaults" => $vaults
            ];
        } catch (\Exception $e) {
            $payload = [
                "error" => true,
                "message" => $e->getMessage()
            ];
        }

        $this->_sendJson($payload);
    }
}
