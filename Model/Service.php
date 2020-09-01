<?php

namespace Cabride\Model;

use Core\Model\Base;
use Siberian\Exception;
use Siberian\Cron;
use Cron_Model_Cron as Task;

/**
 * Class Service
 * @package Cabride\Model
 */
class Service extends Base
{

    /**
     * @param Cron $cron
     * @param Task $task
     * @throws \Exception
     */
    public static function serve($cron, $task)
    {
        if (!self::selfServe()) {
            $cron->log('CabRide service is running outside cron.');
            return;
        }

        if (self::serviceStatus()) {
            $cron->log('CabRide server already running.');
            return;
        }

        $base_path = path('');
        $log_path = path('var/log/modules/cabride.log');
        if (!is_dir(dirname($log_path)) && !mkdir(dirname($log_path), 0777, true)) {
            $log_path = path('var/log/cabride.log');
        }
        $base_node = path('app/local/modules/Cabride/resources/server');
        $bin_path = __get('cabride_node_path');

        if (!$bin_path) {
            $cron->log('Node is not installed.');
            return;
        }

        $command = sprintf("%s %s/cabride.js %s >> %s 2>&1", $bin_path, $base_node, $base_path, $log_path);

        try {
            echo $command . PHP_EOL;
            exec($command, $output, $return);
        } catch (\Exception $e) {
            $cron->log('CabRide server exception.');
        }

        if ($log = @file_get_contents($log_path)) {
            $pos = strrpos($log, '---STARTING RTC---');
            if ($pos >= 0) {
                $log = substr($log, $pos);

                preg_match("/^\[Error\:(.*)\]$/m", $log, $matches);

                if (count($matches) > 0) {
                    throw new Exception(trim($matches[1]));
                }
            }
        }
    }

    /**
     * @param \Cron_Model_Cron $cron
     * @throws \Zend_Exception
     */
    public static function watch ($cron)
    {
        $cron->log("[Cabride] watcher start");

        // Expire requests
        Request::toExpire($cron);

        $cron->log("[Cabride] watcher done");
    }

    /**
     * @param $cron
     */
    public static function bulk ($cron)
    {
        $cron->log("[Cabride] bulk payouts start");

        PayoutBulk::toGenerate($cron);
        Cashreturn::toGenerate($cron);

        $cron->log("[Cabride] bulk payouts end");
    }

    /**
     * @return bool
     */
    public static function serviceStatus(): bool
    {
        $base_node = path('app/local/modules/Cabride/resources/server');
        $bin_path = __get('cabride_node_path');

        $command = sprintf("%s %s/status.js", $bin_path, $base_node);

        try {
            echo $command . PHP_EOL;
            exec($command, $output, $return);
            return !(boolean) $return;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * @return bool
     */
    public static function selfServe(): bool
    {
        $selfServe = __get('cabride_self_serve');

        return $selfServe === 'true';
    }

    /**
     * @return bool
     */
    public static function killServer(): bool
    {
        $serverPid = path('/app/local/modules/Cabride/resources/server/server.pid');
        if (is_readable($serverPid)) {
            $pid = file_get_contents($serverPid);
            $kill = sprintf("kill -9 %s", $pid);
            echo $kill . PHP_EOL;
            exec($kill);

            return true;
        }
        return false;
    }
}
