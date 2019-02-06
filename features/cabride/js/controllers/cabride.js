/**
 * Cabride version 2 controllers
 */
angular.module('starter')
.controller('CabrideHome', function ($window, $scope, $rootScope, $timeout, $translate, $ionicSideMenuDelegate,
                                     Cabride, CabrideUtils, Customer, ContextualMenu, GoogleMaps, Dialog, Location, SB) {
    angular.extend($scope, {
        pageTitle: $translate.instant("CabRide"),
        valueId: Cabride.getValueId(),
        isAlive: Cabride.isAlive,
        isLoggedIn: Customer.isLoggedIn(),
        isLoading: true,
        customer: null,
        crMap: null,
        crMapPin: null,
        driverMarkers: [],
        gmapsAutocompleteOptions: {},
        ride: {
            isSearching: false,
            pickupPlace: null,
            pickupAddress: "",
            dropoffPlace: null,
            dropoffAddress: "",
            distance: null,
            duration: null,
        },
        isPassenger: false,
        isDriver: false,
        removeSideMenu: null
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

    $scope.isTaxiLayout = function () {
        return Cabride.isTaxiLayout;
    };

    $scope.toggleRightMenu = function () {
        // Toggling nav
        ContextualMenu.toggle();
    };

    $scope.buildContextualMenu = function () {
        $scope.removeSideMenu = ContextualMenu.set(
            "./features/cabride/assets/templates/l1/nav/contextual-menu.html",
            "275",
            function () {
                return true;
            });
    };

    // Passenger / Driver choice!
    $scope.selectPassenger = function () {
        // Check if the user is logged in
        if (!Customer.isLoggedIn()) {
            Customer.loginModal($scope,
                /** Login */
                function () {
                    $scope.setIsPassenger(true);
                },
                /** Logout */
                function () {},
                /** Register */
                function () {
                    $scope.setIsPassenger(true);
                });
        } else {
            $scope.setIsPassenger(true);
        }
    };

    $scope.setIsPassenger = function (update) {
        Cabride.setIsPassenger(update);
        $scope.isPassenger = true;
        $scope.isDriver = false;
        $scope.rebuild();
    };

    $scope.selectDriver = function () {
        // Opens driver form
        // Check if the user is logged in
        if (!Customer.isLoggedIn()) {
            Customer.loginModal($scope,
                /** Login */
                function () {
                    $scope.setIsDriver(true);
                },
                /** Logout */
                function () {},
                /** Register */
                function () {
                    $scope.setIsDriver(true);
                });
        } else {
            $scope.setIsDriver(true);
        }
    };

    $scope.setIsDriver = function (update) {
        Cabride.setIsDriver(update);
        $scope.isPassenger = false;
        $scope.isDriver = true;
        $scope.rebuild();
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
            url: "./features/cabride/assets/templates/images/004-blank.png",
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
        for (var indexMarker in $scope.driverMarkers) {
            var driverMarker = $scope.driverMarkers[indexMarker];
            driverMarker.setMap(null);
        }
        $scope.driverMarkers = [];

        for (var indexDriver in drivers) {
            var driver = drivers[indexDriver];
            var myLatlng = new google.maps.LatLng(driver.position.latitude, driver.position.longitude);

            GoogleMaps
            .reverseGeocode({latitude: driver.position.latitude, longitude: driver.position.longitude})
            .then(function (results) {
                if (results.length > 0) {
                    var nearest = results[0];

                    var a = {
                        lat: function () {
                            return driver.position.latitude;
                        },
                        lng: function () {
                            return driver.position.longitude;
                        }
                    };
                    var b = nearest.geometry.location;
                    var heading = google.maps.geometry.spherical.computeHeading(a, b);
                    console.log("heading", heading);

                    var icon = {
                        url: CabrideUtils.taxiIcon(heading),
                        size: new google.maps.Size(120, 120),
                        scaledSize: new google.maps.Size(36, 36),
                        origin: new google.maps.Point(0, 0),
                        anchor: new google.maps.Point(18, 18)
                    };

                    var tmpMarker = new google.maps.Marker({
                        position: myLatlng,
                        map: $scope.crMap,
                        icon: icon,
                    });
                    $scope.driverMarkers.push(tmpMarker);
                }
            });
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

    $scope.canClear = function () {
        return $scope.ride.isSearching ||
            ($scope.ride.pickupAddress !== "") ||
            ($scope.ride.dropoffAddress !== "");
    };

    $scope.driverCanRegister = function () {
        return Cabride.settings.driverCanRegister;
    };

    // Pristine ride values!
    $scope.clearSearch = function () {
        $scope.ride = {
            isSearching: false,
            pickupPlace: null,
            pickupAddress: "",
            dropoffPlace: null,
            dropoffAddress: "",
            distance: null,
            duration: null,
        };

        CabrideUtils.clearRoute();
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
        if ($scope.ride.isSearching) {
            return {
                action: "loading",
                text: ""
            };
        }
        if ($scope.ride.pickupAddress !== "" && $scope.ride.dropoffAddress !== "") {
            return {
                action: "search",
                text: $translate.instant("Request a ride!")
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

                        $scope.setPickupAddress();
                    } else {
                        $scope.ride.pickupAddress = center.lat() + "," + center.lng();

                        $scope.setPickupAddress();
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

                        $scope.setDropoffAddress();
                    } else {
                        $scope.ride.dropoffAddress = center.lat() + "," + center.lng();

                        $scope.setDropoffAddress();
                    }
                }, function () {
                    Dialog.alert(
                        "Position",
                        "Your position doesn't resolve to a valid address.",
                        "OK");
                });
                break;
            case "search":
                    $scope.ride.isSearching = true;
                    $timeout(function () {
                        $scope.ride.isSearching = false;
                    }, 10000);
                break;
            case "none": default:
                console.log("setPinLocation(none)");
                break;
        }
    };

    $scope.setPickupAddress = function () {
        $scope.checkRoute();
    };

    $scope.setDropoffAddress = function () {
        $scope.checkRoute();
    };

    $scope.checkRoute = function () {
        if ($scope.ride.pickupPlace && $scope.ride.dropoffPlace) {
            var pickup = {
                latitude: $scope.ride.pickupPlace.geometry.location.lat(),
                longitude: $scope.ride.pickupPlace.geometry.location.lng(),
            };
            var dropoff = {
                latitude: $scope.ride.dropoffPlace.geometry.location.lat(),
                longitude: $scope.ride.dropoffPlace.geometry.location.lng(),
            };

            CabrideUtils
            .getSimpleDirection(
                pickup,
                dropoff,
                {}
            ).then(function (route) {
                CabrideUtils.displayRoute($scope.crMap, route);
                var leg = _.get(route, "routes[0].legs[0]", false);
                if (leg) {
                    $scope.ride.distance = leg.distance.text;
                    $scope.ride.duration = leg.duration.text;
                }
            }, function () {
                // Clear route
            });
        }
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

                    $scope.setPickupAddress();
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

                    $scope.setDropoffAddress();
                }
            }, function () { // Error!
                Dialog.alert(
                    "Position",
                    "Your position doesn't resolve to a valid address.",
                    "OK");
            })
        }, function () { // Error!
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

    // On load!
    $scope.init = function () {
        Cabride
        .init()
        .then(function () {
            Customer
            .find()
            .then(function (customer) {
                if (!customer.is_logged_in) {
                    Customer.loginModal($scope,
                        /** Login */
                        $scope.init,
                        /** Logout */
                        function () {},
                        /** Register */
                        $scope.init);

                    $scope.isLoading = false;
                    return;
                }

                $scope.customer = customer;
                $scope.customer.metadatas = _.isObject($scope.customer.metadatas)
                    ? $scope.customer.metadatas
                    : {};
                $scope.avatarUrl = Customer.getAvatarUrl($scope.customer.id);

                Cabride
                .fetchUser()
                .then(function (payload) {
                    if (!payload.user) {
                        if (!Cabride.settings.driverCanRegister) {
                            $scope.selectPassenger();
                            $scope.isLoading = false;
                        }
                        return;
                    }
                    switch (payload.user.type) {
                        case 'driver':
                            $scope.setIsDriver(false);
                            break;
                        case 'passenger':
                            $scope.setIsPassenger(false);
                            break;
                    }

                    $scope.isLoading = false;
                });
            });
        });
    };

    $scope.init();

    $scope.buildContextualMenu();

    // Asking for the current layout!
    $rootScope.$broadcast("cabride.isTaxiLayoutActive");

    $rootScope.$on(SB.EVENTS.AUTH.logoutSuccess, function () {
        $scope.init();
    });

    $rootScope.$on(SB.EVENTS.AUTH.loginSuccess, function () {
        $scope.init();
    });

    $rootScope.$on(SB.EVENTS.AUTH.registerSuccess, function () {
        $scope.init();
    });

    $rootScope.$on(SB.EVENTS.AUTH.editSuccess, function () {
        $scope.init();
    });

    $window.cabride = {
        setIsLoading: function (isLoading) {
            $scope.isLoading = isLoading;
        },
        setIsDriver: function (save) {
            $scope.setIsDriver(save);
        },
        setIsPassenger: function (save) {
            $scope.setIsPassenger(save);
        },
        rebuild: function () {
            $scope.rebuild();
        }
    };
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
.controller('CabridePaymentHistory', function ($scope, $translate, Cabride, CabridePayment) {
    angular.extend($scope, {
        pageTitle: $translate.instant("Payment history"),
        valueId: Cabride.getValueId()
    });

    $scope.payNow = function () {
        CabridePayment.pay();
    };
});

angular.module('starter')
.controller('CabrideContextualMenuController', function ($scope, $rootScope, $state,
                                                         $ionicSideMenuDelegate,
                                                         $ionicHistory, Customer,
                                                         SB) {
    angular.extend($scope, {
        isOnline: false,
        customer: null,
        isLoggedIn: Customer.isLoggedIn(),
        isDriver: true,
    });

    /**
     * @param identifier
     */
    $scope.loadPage = function (identifier) {
        if ($ionicSideMenuDelegate.isOpenLeft()) {
            $ionicSideMenuDelegate.toggleLeft();
        }
        if ($ionicSideMenuDelegate.isOpenRight()) {
            $ionicSideMenuDelegate.toggleRight();
        }
        switch (identifier) {
            case "cabride-home":
                $state.go("cabride-home");
                break;
            case "pending-requests":
                $state.go("cabride-pending-requests");
                break;
            case "accepted-requests":
                $state.go("cabride-accepted-requests");
                break;
            case "completed-rides":
                $state.go("cabride-completed-rides");
                break;
            case "cancelled":
                $state.go("cabride-cancelled");
                break;
            case "vehicle-information":
                $state.go("cabride-vehicle-information");
                break;
            case "payment-history":
                $state.go("cabride-payment-history");
                break;
        }
    };

    $scope.toggleStatus = function () {
        $scope.isOnline = !$scope.isOnline;

        // Broadcasting online/offline status
        $rootScope.$broadcast("cabride.isOnline", $scope.isOnline);
    };

    $scope.loginOrSignup = function () {
        Customer.loginModal($scope);
    };

    $scope.customerName = function () {
        if ($scope.customer &&
            $scope.customer.firstname &&
            $scope.customer.lastname) {
            var fname = $scope.customer.firstname.toLowerCase();
            fname = fname.charAt(0).toUpperCase() + fname.slice(1);
            var lname = $scope.customer.lastname.toUpperCase();

            return fname + ' ' + lname
        }
        return '';
    };

    // On load!
    Customer.find()
    .then(function(customer) {
        $scope.customer = customer;
        $scope.customer.metadatas = _.isObject($scope.customer.metadatas)
            ? $scope.customer.metadatas
            : {};
        $scope.avatarUrl = Customer.getAvatarUrl($scope.customer.id);

        return customer;
    });

    /**
     * Hooks
     */
    $scope.rebuildMenu = function () {
        $scope.isLoggedIn = Customer.isLoggedIn();
        if ($scope.isLoggedIn) {
            Customer
            .find()
            .then(function(customer) {
                $scope.customer = customer;
                $scope.customer.metadatas = _.isObject($scope.customer.metadatas)
                    ? $scope.customer.metadatas
                    : {};
                $scope.avatarUrl = Customer.getAvatarUrl($scope.customer.id);

                return customer;
            });
        } else {
            $scope.customer = null;
        }
    };

    $rootScope.$on(SB.EVENTS.AUTH.logoutSuccess, function () {
        $scope.rebuildMenu();
    });

    $rootScope.$on(SB.EVENTS.AUTH.loginSuccess, function () {
        $scope.rebuildMenu();
    });

    $rootScope.$on(SB.EVENTS.AUTH.registerSuccess, function () {
        $scope.rebuildMenu();
    });

    $rootScope.$on(SB.EVENTS.AUTH.editSuccess, function () {
        $scope.rebuildMenu();
    });

    $rootScope.$on("cabride.isPassenger", function () {
        $scope.isDriver = false;
        $scope.rebuildMenu();
    });

    $rootScope.$on("cabride.isDriver", function () {
        $scope.isDriver = true;
        $scope.rebuildMenu();
    });

});

