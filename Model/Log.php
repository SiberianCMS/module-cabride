<?php

namespace Cabride\Model;

use Core\Model\Base;

/**
 * Class Log
 * @package Cabride\Model
 *
 * @method integer getId()
 * @method Db\Table\Log getTable()
 */
class Log extends Base
{
    /**
     * Client constructor.
     * @param array $params
     * @throws \Zend_Exception
     */
    public function __construct($params = [])
    {
        parent::__construct($params);
        $this->_db_table = Db\Table\Log::class;
    }

    /**
     * @param $message
     * @return void
     */
    public static function write($message)
    {
        $valueId = Cabride::getCurrentValueId();

        $log = new Log();
        $log->setValueId($valueId);
        $log->setLog($message);
        $log->save();
    }

    public static function writeDriverRequest($step, $requestId, $drivers)
    {
        $driversIds = [];
        foreach ($drivers as $driver) {
            $driversIds[] = $driver->getDriverId();
        }
        $message = "Sending messages [" . $step . "] to " . count($driversIds) . " drivers, requestId " . $requestId . ", " . implode_polyfill($driversIds, ",");
        self::write($message);
    }

    public static function writeClientRequest($step, $requestId, $client)
    {
        self::write("Sending messages [" . $step . "] to client " . $client->getClientId() . ", requestId " . $requestId . "");
    }
}