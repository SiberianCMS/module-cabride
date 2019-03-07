<?php

namespace Cabride\Model\Payment\Twocheckout;

use Twocheckout;
use Twocheckout_Charge;
use Twocheckout_Error;

/**
 * Class Charge
 * @package Cabride\Model\Payment\Twocheckout
 */
class Charge
{
    /**
     * Merchant Code / Seller Id
     *
     * @var string
     */
    private $merchantCode;

    /**
     * @var string
     */
    private $apiPrivateKey;

    /**
     * @var bool
     */
    private $isSandbox;

    /**
     * Charge constructor.
     * @param $merchantCode
     * @param $apiPrivateKey
     * @param bool $isSandbox
     */
    public function __construct($merchantCode, $apiPrivateKey, $isSandbox = false)
    {
        Twocheckout::sellerId($merchantCode);
        Twocheckout::privateKey($apiPrivateKey);
        Twocheckout::sandbox($isSandbox);

        $this->merchantCode = $merchantCode;
        $this->apiPrivateKey = $apiPrivateKey;
        $this->isSandbox = $isSandbox;
    }

    /**
     * @param $cardToken
     * @return array|mixed|string
     */
    public function createSale($cardToken)
    {
        try {
            $payload = [
                //"sellerId" => $this->merchantCode,
                //"privateKey" => $this->apiPrivateKey,
                "merchantOrderId" => time(),
                "token" => $cardToken,
                "currency" => "EUR",
                "total" => "0.00",
                "billingAddr" => [
                    "name" => "Alex-Yves CREPIN",
                    "addrLine1" => "37 Chemin de Lasbadorques",
                    "city" => "Cornebarrieu",
                    "state" => "",
                    "zipCode" => "31700",
                    "country" => "FR",
                    "email" => "a.crepin@xtraball.com",
                    "phoneNumber" => "0629380343"
                ],
            ];
            
            echo "<pre>";
            print_r(Twocheckout::$baseUrl);
            print_r($payload);

            $charge = Twocheckout_Charge::auth($payload);
            //if ($charge["response"]["responseCode"] === "APPROVED") {
            //    return $charge["response"];
            //}
        } catch (Twocheckout_Error $e) {
            return  $e->getMessage();
        }

        return $charge;
    }
}