<?php

namespace Cabride\Model\Payment\Avangate;

use Siberian\Json;

/**
 * Class Payouts
 * @package Cabride\Model\Payment\Avangate
 */
class Payouts extends ClientAbstract
{
    /**
     * @throws \Exception
     */
    public function getPayouts()
    {
        try {
            $rawResponse = $this->client->get("payouts/pending/");
            $payouts = Json::decode($rawResponse->getBody()->getContents());

            return $payouts;
        } catch (\Exception $e) {
            throw $e;
        }
    }
}