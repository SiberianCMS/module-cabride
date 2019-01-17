/**
 * Cabride version 2 controllers
 */
angular.module('starter')
.controller('CabrideHome', function ($scope, $rootScope, $timeout, $translate, $ionicSideMenuDelegate,
                                     Cabride, Customer, GoogleMaps, Dialog, Location) {
    angular.extend($scope, {
        pageTitle: $translate.instant("CabRide"),
        valueId: Cabride.getValueId(),
        isAlive: Cabride.isAlive,
        isLoggedIn: Customer.isLoggedIn(),
        crMap: null,
        crMapPin: null,
        crPinText: $translate.instant("Set Pick-Up Location"),
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
                "Location",
                "Sorry we are unable to locate you, please check your GPS settings & authorization.",
                "OK");
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
                text: $translate.instant("Set Pick-Up Location")
            };
        }
        if ($scope.ride.dropoffAddress === "") {
            return {
                action: "dropoff",
                text: $translate.instant("Set Drop-Off Location")
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
                        "Position",
                        "Your position doesn't resolve to a valid address.",
                        "OK");
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
                        "Position",
                        "Your position doesn't resolve to a valid address.",
                        "OK");
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
                    "Position",
                    "Your position doesn't resolve to a valid address.",
                    "OK");
            })
        }, function () {
            Dialog.alert(
                "Location",
                "Sorry we are unable to locate you, please check your GPS settings & authorization.",
                "OK");
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
                    "Position",
                    "Your position doesn't resolve to a valid address.",
                    "OK");
            })
        }, function () {
            Dialog.alert(
                "Location",
                "Sorry we are unable to locate you, please check your GPS settings & authorization.",
                "OK");
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
        pageTitle: $translate.instant("Pending requests"),
        valueId: Cabride.getValueId()
    });
});

angular.module('starter')
.controller('CabrideAcceptedRequests', function ($scope, $translate, Cabride) {
    angular.extend($scope, {
        pageTitle: $translate.instant("Accepted requests"),
        valueId: Cabride.getValueId()
    });
});

angular.module('starter')
.controller('CabrideCompletedRides', function ($scope, $translate, Cabride) {
    angular.extend($scope, {
        pageTitle: $translate.instant("Completed rides"),
        valueId: Cabride.getValueId()
    });
});

angular.module('starter')
.controller('CabrideCancelled', function ($scope, $translate, Cabride) {
    angular.extend($scope, {
        pageTitle: $translate.instant("Cancelled"),
        valueId: Cabride.getValueId()
    });
});

angular.module('starter')
.controller('CabrideVehicleInformation', function ($scope, $translate, Cabride) {
    angular.extend($scope, {
        pageTitle: $translate.instant("Vehicle information"),
        valueId: Cabride.getValueId()
    });
});

angular.module('starter')
.controller('CabridePaymentHistory', function ($scope, $translate, Cabride) {
    angular.extend($scope, {
        pageTitle: $translate.instant("Payment history"),
        valueId: Cabride.getValueId()
    });
});