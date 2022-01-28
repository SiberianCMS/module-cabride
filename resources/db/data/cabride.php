<?php

use Siberian\Feature;



try {
    $module = (new Installer_Model_Installer_Module())
        ->prepare('Cabride');

    try {
        $jobs = (new \Cron_Model_Cron())->findAll(['module_id' => $module->getId()]);
        foreach ($jobs as $job) {
            $job->delete();
        }
    } catch (\Exception $e) {
        // Clean up for services!
    }

    Feature::installCronjob(
        __('Cabride, uws Server.'),
        '\\\\Cabride\\\\Model\\\\Service::serve',
        -1,
        -1,
        -1,
        -1,
        -1,
        true,
        100,
        true,
        $module->getId()
    );

    Feature::installCronjob(
        __('Cabride, watcher.'),
        '\\\\Cabride\\\\Model\\\\Service::watch',
        -1,
        -1,
        -1,
        -1,
        -1,
        true,
        100,
        false,
        $module->getId()
    );

    Feature::installCronjob(
        __('Cabride, payouts.'),
        '\\\\Cabride\\\\Model\\\\Service::bulk',
        0,
        -1,
        -1,
        -1,
        -1,
        true,
        100,
        false,
        $module->getId()
    );

    # Chmod +x allow for execution
    exec('chmod +x ' .
        path('/app/local/modules/Cabride/resources/server/bin/') .
        '*');

    $binPath = false;

    # MacOSX
    $is_darwin = exec('uname');
    if (strpos($is_darwin, 'arwin') !== false) {
        $binPath = path('/app/local/modules/Cabride/resources/server/bin/node_64.osx');
    } else {
        $bin64 = path('/app/local/modules/Cabride/resources/server/bin/node_64');
        exec($bin64 . ' --version 2>&1', $output, $returnVal);

        if ($returnVal === 0) {
            $binPath = $bin64;
        }
    }
} catch (\Exception $e) {
    $binPath = false;
}

$data = [
    'code' => 'cabride_node_path',
    'label' => 'CabRide node path',
    'value' => $binPath,
];

$config = new System_Model_Config();
$config
    ->setData($data)
    ->insertOrUpdate(['code']);

// Defaults to enable!
$enableService = __get('cabride_self_serve');
if (empty($enableService)) {
    __set('cabride_self_serve', 'true');
}
