<?php
/**
 * Class Cabride_Model_Service
 */
class Cabride_Model_Service extends Core_Model_Default {

    /**
     * @param Siberian_Cron $cron
     * @param Cron_Model_Cron $task
     */
    public static function serve($cron, $task) {
        if (self::serviceStatus()) {
            $cron->log('CabRide server already running.');
            return;
        }

        $base_path = Core_Model_Directory::getBasePathTo('');
        $log_path = Core_Model_Directory::getBasePathTo('var/log/modules/cabride.log');
        if(!is_dir(dirname($log_path)) && !mkdir(dirname($log_path), 0777, true)) {
            $log_path = Core_Model_Directory::getBasePathTo('var/log/cabride.log');
        }
        $base_node = Core_Model_Directory::getBasePathTo('app/local/modules/Cabride/resources/server');
        $bin_path = __get('cabride_node_path');

        if (!$bin_path) {
            $cron->log('Node is not installed.');
            return;
        }

        $command =  sprintf("%s %s/cabride.js %s >> %s 2>&1", $bin_path, $base_node, $base_path, $log_path);

        try {
            exec($command, $output, $return);
        } catch(Exception $e) {
            $cron->log('CabRide server exception.');
        }

        if ($log = @file_get_contents($log_path)) {
            $pos = strrpos($log, '---STARTING RTC---');
            if ($pos >= 0) {
                $log = substr($log, $pos);

                preg_match("/^\[Error\:(.*)\]$/m", $log, $matches);

                if (count($matches) > 0) {
                    throw new \Exception(trim($matches[1]));
                }
            }
        }
    }

    /**
     * @return bool
     */
    public static function serviceStatus() {
        $base_node = Core_Model_Directory::getBasePathTo('app/local/modules/Cabride/resources/server');
        $bin_path = __get('cabride_node_path');

        $command =  sprintf("%s %s/status.js", $bin_path, $base_node);

        try {
            echo $command . PHP_EOL;
            exec($command, $output, $return);
            file_put_contents('/tmp/debug.log', print_r(!(boolean) $return, true), FILE_APPEND);
            return !(boolean) $return;
        } catch(Exception $e) {
            return false;
        }
    }

    /**
     * @return bool
     */
    public static function killServer() {
        return true;
    }
}