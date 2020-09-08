<?php

$requests = [
    // Emptied!
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
