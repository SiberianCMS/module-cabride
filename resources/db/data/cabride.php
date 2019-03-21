<?php

try {
    $module = (new Installer_Model_Installer_Module())
        ->prepare('Cabride');

    Siberian\Feature::installCronjob(
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

    Siberian\Feature::installCronjob(
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

    $bin64 = path('/app/local/modules/Cabride/resources/server/bin/node_64');
    exec($bin64 . ' --version 2>&1', $output, $returnVal);

    if ($returnVal === 0) {
        $binPath = $bin64;
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
