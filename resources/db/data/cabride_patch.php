<?php

$requests = [
    // 4.0.0 - cleanup empty builk exports*
    "DELETE FROM `cabride_payout_bulk` WHERE `total` = 0 AND `driver_ids` = '' AND `payout_ids` = '' AND `payment_ids` = '';"
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
