<?php

namespace Cabride\Model\Stripe;

use Siberian\Json;
use Siberian\Exception;

/**
 * Class Currency
 * @package Cabride\Model\Stripe
 */
class Currency
{
    /**
     * @var array
     */
    public static $supported = [
        "AED",
        "AFN",
        "ALL",
        "AMD",
        "ANG",
        "AOA",
        "ARS",
        "AUD",
        "AWG",
        "AZN",
        "BAM",
        "BBD",
        "BDT",
        "BGN",
        "BIF",
        "BMD",
        "BND",
        "BOB",
        "BRL",
        "BSD",
        "BWP",
        "BZD",
        "CAD",
        "CDF",
        "CHF",
        "CLP",
        "CNY",
        "COP",
        "CRC",
        "CVE",
        "CZK",
        "DJF",
        "DKK",
        "DOP",
        "DZD",
        "EGP",
        "ETB",
        "EUR",
        "FJD",
        "FKP",
        "GBP",
        "GEL",
        "GIP",
        "GMD",
        "GNF",
        "GTQ",
        "GYD",
        "HKD",
        "HNL",
        "HRK",
        "HTG",
        "HUF",
        "IDR",
        "ILS",
        "INR",
        "ISK",
        "JMD",
        "JPY",
        "KES",
        "KGS",
        "KHR",
        "KMF",
        "KRW",
        "KYD",
        "KZT",
        "LAK",
        "LBP",
        "LKR",
        "LRD",
        "LSL",
        "MAD",
        "MDL",
        "MGA",
        "MKD",
        "MNT",
        "MOP",
        "MRO",
        "MUR",
        "MVR",
        "MWK",
        "MXN",
        "MYR",
        "MZN",
        "NAD",
        "NGN",
        "NIO",
        "NOK",
        "NPR",
        "NZD",
        "PAB",
        "PEN",
        "PGK",
        "PHP",
        "PKR",
        "PLN",
        "PYG",
        "QAR",
        "RON",
        "RSD",
        "RUB",
        "RWF",
        "SAR",
        "SBD",
        "SCR",
        "SEK",
        "SGD",
        "SHP",
        "SLL",
        "SOS",
        "SRD",
        "STD",
        "SVC",
        "SZL",
        "THB",
        "TJS",
        "TOP",
        "TRY",
        "TTD",
        "TWD",
        "TZS",
        "UAH",
        "UGX",
        "USD",
        "UYU",
        "UZS",
        "VND",
        "VUV",
        "WST",
        "XAF",
        "XCD",
        "XOF",
        "XPF",
        "YER",
        "ZAR",
        "ZMW"
    ];

    /**
     * @var array
     */
    public static $zeroDecimals = [
        "BIF",
        "CLP",
        "DJF",
        "GNF",
        "JPY",
        "KMF",
        "KRW",
        "MGA",
        "PYG",
        "RWF",
        "VND",
        "VUV",
        "XAF",
        "XOF",
        "XPF"
    ];

    /**
     * @var null
     */
    public static $jsonSource = null;

    /**
     * @return bool
     */
    public static function getAllCurrencies ()
    {
        if (self::$jsonSource === null) {
            $contents = file_get_contents(path("/app/local/modules/Cabride/Model/Stripe/common-currency.json"));
            self::$jsonSource = Json::decode($contents);
        }

        $common = array_keys(self::$jsonSource);
        $commonCurrencies = array_combine($common, $common);
        foreach (self::$supported as $stripeSupported) {
            $commonCurrencies[$stripeSupported] = "{$stripeSupported} (Stripe)";
        }

        ksort($commonCurrencies);

        return $commonCurrencies;
    }

    /**
     * @param $code
     * @return mixed
     * @throws Exception
     */
    public static function getCurrency ($code)
    {
        if (self::$jsonSource === null) {
            $contents = file_get_contents(path("/app/local/modules/Cabride/Model/Stripe/common-currency.json"));
            self::$jsonSource = Json::decode($contents);
        }

        if (array_key_exists($code, self::$jsonSource)) {
            return self::$jsonSource[$code];
        }

        throw new Exception(p__("cabride", "Invalid currency `%s`.", $code));
    }
}