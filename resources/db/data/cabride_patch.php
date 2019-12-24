<?php

$requests = [
    // cabride_request_log
    "UPDATE cabride_request_log SET timestamp = UNIX_TIMESTAMP(created_at) WHERE timestamp = 0;",
    // cabride_request
    "UPDATE cabride_request SET requested_at = UNIX_TIMESTAMP(created_at) WHERE requested_at = 0;",
    "UPDATE cabride_request JOIN cabride ON cabride_request.value_id = cabride.value_id SET cabride_request.expires_at = (UNIX_TIMESTAMP(cabride_request.created_at) + cabride.search_timeout) WHERE cabride_request.expires_at = 0;",
    // cabride_request_driver
    "UPDATE cabride_request_driver SET requested_at = UNIX_TIMESTAMP(created_at) WHERE requested_at = 0;",
    "UPDATE cabride_request_driver JOIN cabride_request ON cabride_request_driver.request_id = cabride_request.request_id JOIN cabride ON cabride_request.value_id = cabride.value_id SET cabride_request_driver.expires_at = (UNIX_TIMESTAMP(cabride_request_driver.created_at) + cabride.search_timeout) WHERE cabride_request_driver.expires_at = 0;",
    // cabride_payment
    "UPDATE cabride_payment AS cp INNER JOIN cabride_client_vault AS ccv ON cp.client_vault_id = ccv.client_vault_id SET cp.brand = ccv.brand, cp.exp = ccv.exp, cp.last = ccv.last",
    "ALTER TABLE `cabride_payment` CHANGE `request_id` `request_id` INT(11) UNSIGNED NULL DEFAULT NULL;",
];

foreach ($requests as $request) {
    try {
        $this->query($request);
    } catch (\Exception $e) {
        // Silently fails!
    }
}

// Calling a node server restart
try {
    exec("pkill -9 node_64 2&>1 &");
} catch (\Exception $e) {
    // Silently fails!
}

// Patching is_deleted > is_removed
$isRemovedPatch = __get("carbide_is_removed_fix");
if ($isRemovedPatch !== "done") {
    $query = "UPDATE cabride_client_vault SET is_removed = is_deleted WHERE 1;";
    try {
        $this->query($query);
    } catch (\Exception $e) {
        // Silently fails!
    }

    __set("carbide_is_removed_fix", "done");
}