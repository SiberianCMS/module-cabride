<?php

# Various ACL
$acls = [
    [
        "code" => "cabride_dashboard",
        "label" => "Dashboard",
        "url" => "cabride/dashboard/index",
    ],
    [
        "code" => "cabride_users",
        "label" => "Users",
        "url" => "cabride/dashboard/users",
    ],
    [
        "code" => "cabride_drivers",
        "label" => "Drivers",
        "url" => "cabride/dashboard/drivers",
    ],
    [
        "code" => "cabride_rides",
        "label" => "Rides",
        "url" => "cabride/dashboard/rides",
    ],
    [
        "code" => "cabride_payments",
        "label" => "Payments",
        "url" => "cabride/dashboard/payments",
    ],
    [
        "code" => "cabride_vehicle_types",
        "label" => "Vehicle types",
        "url" => "cabride/dashboard/vehicle-types",
    ],
    [
        "code" => "cabride_settings",
        "label" => "Settings",
        "url" => "cabride/dashboard/settings",
    ],
];

// Find feature_cabride
$cabride = (new Acl_Model_Resource())->find("feature_cabride", "code");
if (!$cabride->getId()) {
    $cabride
        ->setData(
            [
                "code" => "feature_cabride",
                "label" => "Cabride",
                "url" => "cabride/application/*",
            ]
        )->save();
}

if ($cabride->getId()) {
    foreach ($acls as $acl) {
        $acl["parent_id"] = $cabride->getId();

        $resource = new Acl_Model_Resource();
        $resource
            ->setData($acl)
            ->insertOrUpdate(["code"]);

        if (!empty($acl["children"])) {
            foreach ($acl["children"] as $childResource) {
                $childResource["parent_id"] = $resource->getId();

                $child = new Acl_Model_Resource();
                $child
                    ->setData($childResource)
                    ->insertOrUpdate(["code"]);
            }
        }
    }
}



