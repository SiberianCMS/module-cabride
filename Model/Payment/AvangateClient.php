<?php

namespace Cabride\Model\Payment;

use GuzzleHttp\Client;

/**
 * Class Client
 * @package AvangateClient
 */
class AvangateClient extends Client
{
    /**
     * AvangateClient constructor.
     * @param array $setup
     */
    public function __construct(array $setup)
    {
        $date = gmdate("Y-m-d H:i:s");
        $code = $setup["code"];
        $key = $setup["key"];
        $hash = hash_hmac("md5", strlen($code) . $code . strlen($date) . $date, $key);
        $headers = [
            "X-Avangate-Authentication" => 'code="' . $code . '" date="' . $date . '" hash="' . $hash . '"',
            "Accept" => "application/json",
            "Content-type" => "application/json",
            "verify" => false,
            "proxy" => ""
        ];
        $setup["headers"] = array_key_exists("headers", $setup) ?
            array_merge($setup["headers"], $headers) : $headers;
        unset($setup["code"], $setup["key"], $setup["version"]);

        parent::__construct($setup);
    }
}