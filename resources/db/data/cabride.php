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

    # Test 1
    exec('/usr/local/bin/nodejs --version 2>&1', $output, $returnVal);
    if ($returnVal === 0) {
        $binPath = '/usr/local/bin/nodejs';
    }

    # Test 2
    if (!$binPath) {
        exec('/usr/bin/nodejs --version 2>&1', $output, $returnVal);

        if ($returnVal === 0) {
            $binPath = '/usr/bin/nodejs';
        }
    }

    # Fallback
    if (!$binPath) {
        # Windows
        # $exe_32_path = Core_Model_Directory::getBasePathTo("/app/local/modules/Cabride/resources/bin/node_32.exe");
        # $exe_64_path = Core_Model_Directory::getBasePathTo("/app/local/modules/Cabride/resources/bin/node_32.exe");

        # Fallback with the node binaries
        # MacOSX
        $isDarwin = exec('uname');
        if (strpos($isDarwin, 'arwin') !== false) {
            $binPath = path('/app/local/modules/Cabride/resources/server/bin/node_64.osx');
            # Windows
        } else {
            $bin_32 = path('/app/local/modules/Cabride/resources/server/bin/node_32');
            exec($bin_32 . ' --version 2>&1', $output, $returnVal);

            if ($returnVal === 0) {
                $binPath = $bin_32;
            } else {

                $bin64 = path('/app/local/modules/Cabride/resources/server/bin/node_64');
                exec($bin64 . ' --version 2>&1', $output, $returnVal);

                if ($returnVal === 0) {
                    $binPath = $bin64;
                }
            }
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
