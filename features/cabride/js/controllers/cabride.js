/**
 * Cabride version 2 controllers
 */
angular.module('starter')
.controller('CabrideHome', function ($scope, $rootScope, $timeout, $translate, $ionicSideMenuDelegate,
                                     Cabride, Customer, GoogleMaps, Dialog, Location) {
    angular.extend($scope, {
        pageTitle: $translate.instant("cr0010_cabride"),
        valueId: Cabride.getValueId(),
        isAlive: Cabride.isAlive,
        isLoggedIn: Customer.isLoggedIn(),
        crMap: null,
        crMapPin: null,
        crPinText: $translate.instant("cr0024_set_pickup_location"),
        driverMarkers: [],
        gmapsAutocompleteOptions: {},
        ride: {
            pickupPlace: null,
            pickupAddress: "",
            dropoffPlace: null,
            dropoffAddress: "",
        },
        isPassenger: false,
        isDriver: false,
    });

    $rootScope.$on("cabride.isAlive", function () {
        $timeout(function () {
            $scope.isAlive = true;
        });
    });

    $rootScope.$on("cabride.isGone", function () {
        $timeout(function () {
            $scope.isAlive = false;
        });
    });

    $rootScope.$on("cabride.advertDrivers", function (event, payload) {
        // Refresh driver markers
        $scope.drawDrivers(payload.drivers);
    });

    $scope.$on('$ionicView.enter', function(){
        $ionicSideMenuDelegate.canDragContent(false);
    });
    $scope.$on('$ionicView.leave', function(){
        $ionicSideMenuDelegate.canDragContent(true);
    });

    $scope.reconnect = function () {
        Cabride.init();
    };

    // Passenger / Driver choice!
    $scope.selectPassenger = function () {
        // Check if the user is logged in
        if (!Customer.isLoggedIn()) {
            Customer.loginModal($scope,
                /** Login */
                function () {
                    Cabride.setIsPassenger();
                    $scope.isPassenger = true;
                    $scope.isDriver = false;
                    $scope.rebuild();
                },
                /** Logout */
                function () {},
                /** Register */
                function () {
                    Cabride.setIsPassenger();
                    $scope.isPassenger = true;
                    $scope.isDriver = false;
                    $scope.rebuild();
                });
        } else {
            Cabride.setIsPassenger();
            $scope.isPassenger = true;
            $scope.isDriver = false;
            $scope.rebuild();
        }
    };

    $scope.selectDriver = function () {
        // Opens driver form
        // Check if the user is logged in
        if (!Customer.isLoggedIn()) {
            Customer.loginModal($scope,
                /** Login */
                function () {
                    Cabride.setIsDriver();
                    $scope.isPassenger = false;
                    $scope.isDriver = true;
                    $scope.rebuild();
                },
                /** Logout */
                function () {},
                /** Register */
                function () {
                    Cabride.setIsDriver();
                    $scope.isPassenger = false;
                    $scope.isDriver = true;
                    $scope.rebuild();
                });
        } else {
            Cabride.setIsDriver();
            $scope.isPassenger = false;
            $scope.isDriver = true;
            $scope.rebuild();
        }
    };

    $scope.initMap = function () {
        $scope.crMap = GoogleMaps.createMap("crMap", {
            zoom: 10,
            center: {
                lat: 43.600000,
                lng: 1.433333
            },
            disableDefaultUI: true
        });

        var icon = {
            url: './features/cabride/assets/templates/images/004-blank.png',
            width: 48,
            height: 48,
            origin: new google.maps.Point(0, 0),
            anchor: new google.maps.Point(24, 48)
        };

        var center = $scope.crMap.getCenter();
        $scope.crMapPin = new google.maps.Marker({
            position: center,
            map: $scope.crMap,
            icon: icon
        });

        google.maps.event.addListener($scope.crMap, 'center_changed', function() {
            // 0.5 seconds after the center of the map has changed,
            // set back the marker position.
            $timeout(function() {
                var center = $scope.crMap.getCenter();
                $scope.crMapPin.setPosition(center);
            }, 500);
        });
    };

    $scope.drawDrivers = function (drivers) {
        // Clear markers!
        for(var index in $scope.driverMarkers) {
            let driverMarker = $scope.driverMarkers[index];
            driverMarker.setMap(null);  
        }
        $scope.driverMarkers = [];

        for(var index in drivers) {
            var driver = drivers[index];
            var myLatlng = new google.maps.LatLng(driver.position.latitude, driver.position.longitude);
            var tmpMarker = new google.maps.Marker({
                position: myLatlng,
                map: $scope.crMap,
                icon: null,
            });
            $scope.driverMarkers.push(tmpMarker);
        }
    };

    $scope.centerMe = function () {
        Location
        .getLocation()
        .then(function (position) {
            $scope.crMap.setCenter(new google.maps.LatLng(position.coords.latitude, position.coords.longitude));
            $scope.crMap.setZoom(15);
        }, function () {
            Dialog.alert(
                "cr0019_dialog_location",
                "cr0020_sorry_we_are_unable_to_locate_you_please_check_your_gps_settings_and_authorization",
                "cr0021_dialog_ok");
        });
    };

    $scope.zoomIn = function () {
        $scope.crMap.setZoom($scope.crMap.getZoom() + 1);
    };

    $scope.zoomOut = function () {
        $scope.crMap.setZoom($scope.crMap.getZoom() - 1);
    };

    $scope.pinText = function() {
        if ($scope.ride.pickupAddress === "") {
            return {
                action: "pickup",
                text: $translate.instant("cr0024_set_pickup_location")
            };
        }
        if ($scope.ride.dropoffAddress === "") {
            return {
                action: "dropoff",
                text: $translate.instant("cr0025_set_dropoff_location")
            };
        }
        return {
            action: "none",
            text: ""
        };
    };

    $scope.setPinLocation = function (action) {
        var center = $scope.crMap.getCenter();
        switch (action) {
            case "pickup":
                GoogleMaps
                .reverseGeocode({latitude: center.lat(), longitude: center.lng()})
                .then(function (results) {
                    if (results.length > 0) {
                        var pickupPlace = results[0];
                        $scope.ride.pickupAddress = pickupPlace.formatted_address;
                        $scope.ride.pickupPlace = pickupPlace;
                    } else {
                        $scope.ride.pickupAddress = center.lat() + "," + center.lng();
                    }
                }, function () {
                    Dialog.alert(
                        "cr0022_dialog_position",
                        "cr0023_your_position_doesnt_resolve_to_a_valid_address",
                        "cr0021_dialog_ok");
                });
                break;
            case "dropoff":
                GoogleMaps
                .reverseGeocode({latitude: center.lat(), longitude: center.lng()})
                .then(function (results) {
                    if (results.length > 0) {
                        var dropoffPlace = results[0];
                        $scope.ride.dropoffAddress = dropoffPlace.formatted_address;
                        $scope.ride.dropoffPlace = dropoffPlace;
                    } else {
                        $scope.ride.dropoffAddress = center.lat() + "," + center.lng();
                    }
                }, function () {
                    Dialog.alert(
                        "cr0022_dialog_position",
                        "cr0023_your_position_doesnt_resolve_to_a_valid_address",
                        "cr0021_dialog_ok");
                });
                break;
            case "none": default:
                console.log("setPinLocation(none)");
                break;
        }
    };

    $scope.setPickupAddress = function () {
        console.log("setPickupAddress",
            $scope.ride.pickupAddress,
            $scope.ride.pickupPlace);
    };

    $scope.setDropoffAddress = function () {
        console.log("setDropoffAddress",
            $scope.ride.dropoffAddress,
            $scope.ride.dropoffPlace);
    };

    $scope.geoPickup = function () {
        Location
        .getLocation()
        .then(function (position) {
            GoogleMaps
            .reverseGeocode(position.coords)
            .then(function (results) {
                if (results.length > 0) {
                    var pickupPlace = results[0];
                    $scope.ride.pickupAddress = pickupPlace.formatted_address;
                    $scope.ride.pickupPlace = pickupPlace;
                }
            }, function () {
                Dialog.alert(
                    "cr0022_dialog_position",
                    "cr0023_your_position_doesnt_resolve_to_a_valid_address",
                    "cr0021_dialog_ok");
            })
        }, function () {
            Dialog.alert(
                "cr0019_dialog_location",
                "cr0020_sorry_we_are_unable_to_locate_you_please_check_your_gps_settings_and_authorization",
                "cr0021_dialog_ok");
        });
    };

    $scope.geoDropoff = function () {
        Location
        .getLocation()
        .then(function (position) {
            GoogleMaps
            .reverseGeocode(position.coords)
            .then(function (results) {
                if (results.length > 0) {
                    var dropoffPlace = results[0];
                    $scope.ride.dropoffAddress = dropoffPlace.formatted_address;
                    $scope.ride.dropoffPlace = dropoffPlace;
                }
            }, function () {
                Dialog.alert(
                    "cr0022_dialog_position",
                    "cr0023_your_position_doesnt_resolve_to_a_valid_address",
                    "cr0021_dialog_ok");
            })
        }, function () {
            Dialog.alert(
                "cr0019_dialog_location",
                "cr0020_sorry_we_are_unable_to_locate_you_please_check_your_gps_settings_and_authorization",
                "cr0021_dialog_ok");
        });
    };

    $scope.rebuild = function () {
        $scope.initMap();
    };

    // Init build!
    GoogleMaps
        .ready
        .then(function () {
            $scope.rebuild();
        });
});

angular.module('starter')
.controller('CabridePendingRequests', function ($scope, $translate, Cabride) {
    angular.extend($scope, {
        pageTitle: $translate.instant("cr0011_pending_requests"),
        valueId: Cabride.getValueId()
    });
});

angular.module('starter')
.controller('CabrideAcceptedRequests', function ($scope, $translate, Cabride) {
    angular.extend($scope, {
        pageTitle: $translate.instant("cr0012_accepted_requests"),
        valueId: Cabride.getValueId()
    });
});

angular.module('starter')
.controller('CabrideCompletedRides', function ($scope, $translate, Cabride) {
    angular.extend($scope, {
        pageTitle: $translate.instant("cr0013_completed_rides"),
        valueId: Cabride.getValueId()
    });
});

angular.module('starter')
.controller('CabrideCancelled', function ($scope, $translate, Cabride) {
    angular.extend($scope, {
        pageTitle: $translate.instant("cr0014_cancelled"),
        valueId: Cabride.getValueId()
    });
});

angular.module('starter')
.controller('CabrideVehicleInformation', function ($scope, $translate, Cabride) {
    angular.extend($scope, {
        pageTitle: $translate.instant("cr0015_vehicle_information"),
        valueId: Cabride.getValueId()
    });
});

angular.module('starter')
.controller('CabridePaymentHistory', function ($scope, $translate, Cabride) {
    angular.extend($scope, {
        pageTitle: $translate.instant("cr0016_payment_history"),
        valueId: Cabride.getValueId()
    });
});