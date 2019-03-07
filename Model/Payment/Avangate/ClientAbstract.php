<?php

namespace Cabride\Model\Payment\Avangate;

use Cabride\Model\Payment\AvangateClient;

abstract class ClientAbstract
{
    /**
     * @var AvangateClient
     */
    public $client;

    /**
     * Customer constructor.
     * @param $merchantCode
     * @param $merchantApiKey
     */
    public function __construct($merchantCode, $merchantApiKey)
    {
        $this->client = new AvangateClient([
            "code" => $merchantCode,
            "key" => $merchantApiKey,
            "base_uri" => "https://api.2checkout.com/rest/5.0/"
        ]);
    }
}