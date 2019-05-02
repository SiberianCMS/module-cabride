<?php

use Siberian\Feature;

try {
    $module = (new Installer_Model_Installer_Module())
        ->prepare('Cabride');

    Feature::installCronjob(
        __('Cabride, uws Server.'),
        'CabrideService::serve',
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
        'CabrideService::watch',
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


// 1.3.0 commission patch!
$commissionPatch = __get("cabride_commission_patch");
if ($commissionPatch != "done") {
    $this->query("UPDATE cabride SET commission_fixed = commission WHERE 1;");
    __set("cabride_commission_patch", "done");
}