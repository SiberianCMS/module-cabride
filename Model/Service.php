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
     * @var string
     */
    public static $basePath;

    /**
     * @var string
     */
    public static $logPath;

    /**
     * @var string
     */
    public static $baseNode;

    /**
     * @var string
     */
    public static $binPath;

    /**
     * @var string
     */
    public static $serverPid;

    /**
     * Init global paths
     */
    public static function init ()
    {
        self::$basePath = path();
        self::$logPath = path('/var/log/cabride.log');
        self::$baseNode = path('/app/local/modules/Cabride/resources/server');
        self::$binPath = path('/lib/Siberian/bin/node_64');
        self::$serverPid = path('/app/local/modules/Cabride/resources/server/server.pid');

        // Special dev case!
        try {
            exec('uname', $uname);
            if (stripos(implode_polyfill('', $uname), 'arwin') !== false) {
                self::$binPath .= '.osx';
            }
        } catch (\Exception $e) {
            //Nope!
        }
    }

    /**
     * @param Cron $cron
     * @param Task $task
     * @throws \Exception
     */
    public static function serve($cron, $task)
    {
        self::init();

        if (!self::selfServe()) {
            $cron->log('CabRide service is running outside cron.');
            return;
        }

        if (self::serviceStatus()) {
            $cron->log('CabRide server already running.');
            return;
        }

        $command = sprintf(
            "%s %s/cabride.js %s >> %s 2>&1",
            self::$binPath,
            self::$baseNode,
            self::$basePath,
            self::$logPath);

        try {
            echo $command . PHP_EOL;
            exec($command, $output, $return);
        } catch (\Exception $e) {
            $cron->log('CabRide server exception.');
        }

        $size = filesize(self::$logPath);
        if ($size > 32 * 1024 * 1024) {
            for ($i = 3; $i > 0; $i--) {
                $log = self::$logPath . '.' . $i;
                if (file_exists($log)) {
                    unlink($log);
                }
                $log = self::$logPath . '.' . ($i - 1);
                if (file_exists($log)) {
                    rename($log, self::$logPath . '.' . $i);
                }
            }
            rename(self::$logPath, self::$logPath . '.0');
        }

        if ($log = file_get_contents(self::$logPath)) {
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
        self::init();

        $command = sprintf("%s %s/status.js", self::$binPath, self::$baseNode);

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
        self::init();

        if (is_readable(self::$serverPid)) {
            $pid = file_get_contents(self::$serverPid);
            $kill = sprintf("kill -9 %s", $pid);
            echo $kill . PHP_EOL;
            exec($kill);

            return true;
        }
        return false;
    }
}
