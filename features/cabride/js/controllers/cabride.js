/**
 * Cabride version 2 controllers
 */
angular.module('starter')
.controller('CabrideHome', function ($window, $state, $scope, $rootScope, $timeout, $translate,
                                     $ionicSideMenuDelegate, Modal, Cabride, CabrideUtils, Customer,
                                     Loader, ContextualMenu, GoogleMaps, Dialog, Location, SB, CabridePayment) {
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
            duration: null
        },
        currentRoute: null,
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

    $scope.$on('$ionicView.enter', function () {
        $ionicSideMenuDelegate.canDragContent(false);
    });
    $scope.$on('$ionicView.leave', function () {
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
                function () {
                },
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
                function () {
                },
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

        google.maps.event.addListener($scope.crMap, 'center_changed', function () {
            // 0.5 seconds after the center of the map has changed,
            // set back the marker position.
            $timeout(function () {
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

            var a = {
                lat: function () {
                    return driver.position.latitude;
                },
                lng: function () {
                    return driver.position.longitude;
                }
            };
            var b = {
                lat: function () {
                    return driver.previous.latitude;
                },
                lng: function () {
                    return driver.previous.longitude;
                }
            };
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

    $scope.pinText = function () {
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
                $scope.requestRide();

                break;
            case "none":
            default:
                console.log("setPinLocation(none)");
                break;
        }
    };

    $scope.requestRide = function () {
        $scope.ride.isSearching = true;
        Cabride
        .requestRide($scope.currentRoute)
        .then(function (response) {
            if (response.collection && Object.keys(response.collection).length > 0) {
                $scope.showModal(response.collection);
            } else {
                Dialog.alert("", "We are sorry we didnt found any available driver around you!", "OK");
            }
        }, function (error) {
            Dialog.alert("", "We are sorry we didnt found any available driver around you!", "OK");
        }).then(function () {
            $scope.ride.isSearching = false;
        });
    };

    $scope.vtModal = null;
    $scope.showModal = function (vehicles) {
        Modal
        .fromTemplateUrl("features/cabride/assets/templates/l1/modal/vehicle-type.html", {
            scope: angular.extend($scope.$new(true), {
                close: function () {
                    $scope.vtModal.hide();
                },
                select: function (vehicleType) {
                    $scope.selectVehicle(vehicleType);
                },
                vehicles: vehicles
            }),
            animation: 'slide-in-up'
        }).then(function (modal) {
            $scope.vtModal = modal;
            $scope.vtModal.show();

            return modal;
        });
    };

    $scope.ptModal = null;
    $scope.paymentTypeModal = function (paymentTypes) {
        Modal
        .fromTemplateUrl("features/cabride/assets/templates/l1/modal/payment-type.html", {
            scope: angular.extend($scope.$new(true), {
                close: function () {
                    $scope.ptModal.hide();
                },
                select: function (paymentType) {
                    $scope.selectPaymentType(paymentType);
                },
                pay: function () {
                    CabridePayment.pay();

                    // then ....
                    // $scope.validateRequest();
                },
                hasPaymentType: false,
                settings: Cabride.settings,
                paymentTypes: paymentTypes
            }),
            animation: 'slide-in-up'
        }).then(function (modal) {
            $scope.ptModal = modal;
            $scope.ptModal.show();

            return modal;
        });
    };

    $scope.selectPaymentType = function (paymentType) {
        switch (paymentType) {
            case "credit-card":
                // Pre-authorize payment!
                // Validate directly!
                Dialog
                .alert("Thanks", "PAY NOW!", "OK", 2350)
                .then(function () {
                    //$scope.validateRequest();
                });
                break;
            case "cash":
                // Validate directly!
                Dialog
                .alert("Thanks", "You'll pay the driver directly when the course ends!", "OK", 2350)
                .then(function () {
                    $scope.validateRequest();
                });
                break;
        }
    };

    $scope.selectVehicle = function (vehicleType) {
        // Payment modal
        $scope.vehicleType = vehicleType;
        $scope.paymentTypeModal();
    };

    $scope.validateRequest = function () {
        Loader.show("Sending request ...");
        Cabride
        .validateRequest($scope.vehicleType, $scope.currentRoute)
        .then(function (response) {
            Loader.hide();
            Dialog
            .alert("Request sent", "Please now wait for a driver!", "OK", 2350)
            .then(function () {
                $scope.ptModal.hide();
                $scope.vtModal.hide();
                $state.go("cabride-my-rides");
            });
            // Clear ride
            $scope.clearSearch();
        }, function (error) {
            Loader.hide();
            Dialog
            .alert("Sorry!", error.message, "OK")
            .then(function () {
                $scope.ptModal.hide();
                $scope.vtModal.hide();
                $state.go("cabride-my-rides");
            });
        });
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
                $scope.currentRoute = route;
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
            });
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
        // Must log-in first.
        if (!Customer.isLoggedIn()) {
            Customer.loginModal($scope,
                /** Login */
                $scope.init,
                /** Logout */
                function () {
                },
                /** Register */
                $scope.init);

            $scope.isLoading = false;
            return;
        }

        Cabride
        .init()
        .then(function () {
            Customer
            .find()
            .then(function (customer) {
                $scope.customer = customer;
                $scope.customer.metadatas = _.isObject($scope.customer.metadatas)
                    ? $scope.customer.metadatas
                    : {};
                $scope.avatarUrl = Customer.getAvatarUrl($scope.customer.id);

                Cabride
                .fetchUser()
                .then(function (payload) {
                    switch (payload.user.type) {
                        case 'new':
                            if (!Cabride.settings.driverCanRegister) {
                                $scope.selectPassenger();
                            }
                            break;
                        case 'driver':
                            $scope.setIsDriver(false);
                            $rootScope.$broadcast("cabride.setIsOnline", payload.user.isOnline);
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
        $scope.isDriver = false;
        $scope.isPassenger = false;
        $scope.isLoggedIn = false;
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
.controller('CabrideMyRides', function ($scope, $filter, $translate, $ionicScrollDelegate,
                                        Cabride, CabrideUtils, Dialog) {
    angular.extend($scope, {
        isLoading: false,
        pageTitle: $translate.instant("My rides"),
        valueId: Cabride.getValueId(),
        filtered: null,
        filterName: "in progress",
        collection: [],
        statuses: [
            "pending",
            "accepted",
            "onway",
            "inprogress"
        ]
    });

    $scope.loadPage = function () {
        $scope.isLoading = true;
        Cabride
        .getMyRides()
        .then(function (payload) {
            $scope.collection = payload.collection;
            $scope.filtered = $filter("cabrideStatusFilter")($scope.collection, $scope.statuses);
        }, function (error) {
            Dialog.alert("Error", error.message, "OK");
        }).then(function () {
            $scope.isLoading = false;
        });
    };

    $scope.distance = function (request) {
        return Math.ceil(request.distance / 1000) + "Km";
    };

    $scope.duration = function (request) {
        return CabrideUtils.toHHMM(request.duration);
    };

    $scope.calendar = function (timestampSeconds) {
        return moment(timestampSeconds * 1000).calendar();
    };

    $scope.expiration = function (request) {
        // Ensure values are integers
        var duration = (parseInt(request.timestamp, 10) + parseInt(request.search_timeout, 10)) * 1000;
        return moment(duration).fromNow();
    };

    $scope.refresh = function () {
        $scope.loadPage();
    };

    $scope.imagePath = function (image) {
        if (image === "") {
            return IMAGE_URL + "app/local/modules/Cabride/resources/design/desktop/flat/images/no-route.jpg";
        }
        return IMAGE_URL + "images/application" + image;
    };

    $scope.statusFilter = function (filter) {
        // "pending", "accepted", "declined", "done", "aborted", "expired"
        switch (filter) {
            case "inprogress":
                $scope.filterName = "in progress";
                $scope.statuses = ["pending", "accepted", "onway", "inprogress"];
                break;
            case "archives":
                $scope.filterName = "archived";
                $scope.statuses = ["declined", "done", "aborted", "expired"];
                break;
        }
        $ionicScrollDelegate.scrollTop();
    };

    $scope.$watch("filterName", function () {
        $scope.filtered = $filter("cabrideStatusFilter")($scope.collection, $scope.statuses)
    });

    $scope.loadPage();
});

angular.module('starter')
.controller('CabridePendingRequests', function ($scope, $translate, Cabride, CabrideUtils, Dialog, Loader) {
    angular.extend($scope, {
        isLoading: false,
        pageTitle: $translate.instant("Pending requests"),
        valueId: Cabride.getValueId(),
        collection: []
    });

    $scope.loadPage = function () {
        $scope.isLoading = true;
        Cabride
        .getPendingRides()
        .then(function (payload) {
            $scope.collection = payload.collection;
        }, function (error) {
            Dialog.alert("Error", error.message, "OK");
        }).then(function () {
            $scope.isLoading = false;
        });
    };

    $scope.distance = function (request) {
        var unit = Cabride.settings.distance_unit;
        var distance = request.distance / 1000;
        switch (unit) {
            case "mi":
                return Math.ceil(distance) + " mi";
                break;
            case "km": default:
                return Math.ceil(distance) + " Km";
                break;
        }
    };

    $scope.duration = function (request) {
        return CabrideUtils.toHHMM(request.duration);
    };

    $scope.calendar = function (timestampSeconds) {
        return moment(timestampSeconds * 1000).calendar();
    };

    $scope.expiration = function (request) {
        // Ensure values are integers
        var duration = (parseInt(request.timestamp, 10) + parseInt(request.search_timeout, 10)) * 1000;
        return moment(duration).fromNow();
    };

    $scope.refresh = function () {
        $scope.loadPage();
    };

    $scope.decline = function (requestId) {
        Loader.show();
        Cabride
        .declineRide(requestId)
        .then(function (payload) {
            Dialog
            .alert("", payload.message, "OK", 2350)
            .then(function () {
                Loader.hide();
                $state.go("cabride-cancelled");
            });
        }, function (error) {
            Dialog.alert("Error", error.message, "OK");
        }).then(function () {
            Loader.hide();
            $scope.refresh();
        });
    };

    $scope.accept = function (request) {
        Loader.show();
        CabrideUtils
        .getDirectionWaypoints(
            Cabride.lastPosition,
            {
                latitude: request.from_lat,
                longitude: request.from_lng,
            },
            {
                latitude: request.to_lat,
                longitude: request.to_lng,
            },
            true
        ).then(function (route) {
            Cabride
            .acceptRide(request.request_id, route)
            .then(function (payload) {
                Dialog
                .alert("", payload.message, "OK", 2350)
                .then(function () {
                    Loader.hide();
                    $state.go("cabride-accepted-requests");
                });
            }, function (error) {
                Dialog.alert("Error", error.message, "OK");
            }).then(function () {
                Loader.hide();
                $scope.refresh();
            });
        }, function (error) {
            Dialog.alert("Error", error[1], "OK");
            Loader.hide();
            $scope.refresh();
        });
    };

    $scope.imagePath = function (image) {
        if (image === "") {
            return IMAGE_URL + "app/local/modules/Cabride/resources/design/desktop/flat/images/no-route.jpg";
        }
        return IMAGE_URL + "images/application" + image;
    };

    $scope.loadPage();
});

angular.module('starter')
.controller('CabrideAcceptedRequests', function ($scope, $translate, Cabride, Dialog, Loader) {
    angular.extend($scope, {
        isLoading: false,
        pageTitle: $translate.instant("Accepted requests"),
        valueId: Cabride.getValueId(),
        collection: []
    });

    $scope.loadPage = function () {
        $scope.isLoading = true;
        Cabride
        .getAcceptedRides()
        .then(function (payload) {
            $scope.collection = payload.collection;
        }, function (error) {
            Dialog.alert("Error", error.message, "OK");
        }).then(function () {
            $scope.isLoading = false;
        });
    };

    $scope.distance = function (request) {
        var unit = Cabride.settings.distance_unit;
        var distance = request.distance / 1000;
        switch (unit) {
            case "mi":
                return Math.ceil(distance) + " mi";
                break;
            case "km": default:
            return Math.ceil(distance) + " Km";
            break;
        }
    };

    $scope.duration = function (request) {
        return $scope.toHHMM(request.duration);
    };

    $scope.calendar = function (timestampSeconds) {
        return moment(timestampSeconds * 1000).calendar();
    };

    $scope.refresh = function () {
        $scope.loadPage();
    };

    $scope.driveToPassenger = function (requestId) {
        Loader.show();
        Cabride
        .driveToPassenger(requestId)
        .then(function (payload) {
            Dialog
            .alert("", payload.message, "OK", 2350)
            .then(function () {
                Loader.hide();
                Navigator.navigate(payload.driveTo);
            });
        }, function (error) {
            Dialog.alert("Error", error.message, "OK");
        }).then(function () {
            Loader.hide();
            $scope.refresh();
        });
    };

    $scope.driveToDestination = function (requestId) {
        Loader.show();
        Cabride
        .driveToDestination(requestId)
        .then(function (payload) {
            Dialog
            .alert("", payload.message, "OK", 2350)
            .then(function () {
                Loader.hide();
                Navigator.navigate(payload.driveTo);
            });
        }, function (error) {
            Dialog.alert("Error", error.message, "OK");
        }).then(function () {
            Loader.hide();
            $scope.refresh();
        });
    };

    $scope.details = function (request) {
       alert("Yolo!");
    };

    $scope.imagePath = function (image) {
        if (image === "") {
            return IMAGE_URL + "app/local/modules/Cabride/resources/design/desktop/flat/images/no-route.jpg";
        }
        return IMAGE_URL + "images/application" + image;
    };

    $scope.loadPage();
});

angular.module('starter')
.controller('CabrideCompletedRides', function ($scope, $translate, Cabride, Dialog) {
    angular.extend($scope, {
        isLoading: false,
        pageTitle: $translate.instant("Completed requests"),
        valueId: Cabride.getValueId(),
        collection: []
    });

    $scope.loadPage = function () {
        $scope.isLoading = true;
        Cabride
        .getCompletedRides()
        .then(function (payload) {
            $scope.collection = payload.collection;
        }, function (error) {
            Dialog.alert("Error", error.message, "OK");
        }).then(function () {
            $scope.isLoading = false;
        });
    };

    $scope.distance = function (request) {
        return Math.ceil(request.distance / 1000) + "Km";
    };

    $scope.duration = function (request) {
        return $scope.toHHMM(request.duration);
    };

    $scope.calendar = function (timestampSeconds) {
        return moment(timestampSeconds * 1000).calendar();
    };

    $scope.refresh = function () {
        $scope.loadPage();
    };

    $scope.imagePath = function (image) {
        if (image === "") {
            return IMAGE_URL + "app/local/modules/Cabride/resources/design/desktop/flat/images/no-route.jpg";
        }
        return IMAGE_URL + "images/application" + image;
    };

    $scope.loadPage();
});

angular.module('starter')
.controller('CabrideCancelled', function ($scope, $translate, Cabride, CabrideUtils, Dialog, Loader) {
    angular.extend($scope, {
        isLoading: false,
        pageTitle: $translate.instant("Declined requests"),
        valueId: Cabride.getValueId(),
        collection: []
    });

    $scope.loadPage = function () {
        $scope.isLoading = true;
        Cabride
        .getCancelledRides()
        .then(function (payload) {
            $scope.collection = payload.collection;
        }, function (error) {
            Dialog.alert("Error", error.message, "OK");
        }).then(function () {
            $scope.isLoading = false;
        });
    };

    $scope.distance = function (request) {
        return Math.ceil(request.distance / 1000) + "Km";
    };

    $scope.duration = function (request) {
        return CabrideUtils.toHHMM(request.duration);
    };

    $scope.calendar = function (timestampSeconds) {
        return moment(timestampSeconds * 1000).calendar();
    };

    $scope.expiration = function (request) {
        // Ensure values are integers
        var duration = (parseInt(request.timestamp, 10) + parseInt(request.search_timeout, 10)) * 1000;
        return moment(duration).fromNow();
    };

    $scope.accept = function (requestId) {
        Loader.show();
        Cabride
        .acceptRide(requestId)
        .then(function (payload) {
            Dialog
            .alert("", payload.message, "OK", 2350)
            .then(function () {
                Loader.hide();
                $state.go("cabride-accepted-requests");
            });
        }, function (error) {
            Dialog.alert("Error", error.message, "OK");
        }).then(function () {
            Loader.hide();
            $scope.refresh();
        });
    };

    $scope.refresh = function () {
        $scope.loadPage();
    };

    $scope.imagePath = function (image) {
        if (image === "") {
            return IMAGE_URL + "app/local/modules/Cabride/resources/design/desktop/flat/images/no-route.jpg";
        }
        return IMAGE_URL + "images/application" + image;
    };

    $scope.loadPage();
});

angular.module('starter')
.controller('CabrideVehicleInformation', function ($scope, $translate, Cabride, Dialog, Loader) {
    angular.extend($scope, {
        isLoading: false,
        pageTitle: $translate.instant("Vehicle information"),
        valueId: Cabride.getValueId(),
        pricingMode: Cabride.settings.pricingMode,
        changingType: false,
        collection: []
    });

    $scope.loadPage = function () {
        $scope.isLoading = true;
        Cabride
        .getVehicleInformation()
        .then(function (payload) {
            $scope.vehicleTypes = payload.vehicleTypes;
            $scope.driver = payload.driver;
            $scope.currentType = payload.currentType;
        }, function (error) {
            Dialog.alert("Error", error.message, "OK");
        }).then(function () {
            $scope.isLoading = false;
        });
    };

    $scope.selectType = function (type) {
        Loader.show();
        Cabride
        .selectVehicleType(type.id)
        .then(function (payload) {
            $scope.driver = payload.driver;
            $scope.currentType = payload.currentType;
            $scope.changingType = false;
        }, function (error) {
            Dialog.alert("Error", error.message, "OK");
        }).then(function () {
            Loader.hide();
        });
    };

    $scope.changeType = function () {
        $scope.changingType = true;
    };

    $scope.cancelType = function () {
        $scope.changingType = false;
    };

    $scope.save = function () {
        Loader.show();
        Cabride
        .saveDriver($scope.driver)
        .then(function (payload) {
            $scope.driver = payload.driver;
            Dialog.alert("Saved!", payload.message, "OK");
        }, function (error) {
            Dialog.alert("Error", error.message, "OK");
        }).then(function () {
            Loader.hide();
        });
    };

    $scope.distanceUnit = function () {
        return Cabride.settings.distanceUnit;
    };

    $scope.pricingDriver = function () {
        return Cabride.settings.pricingMode === "driver";
    };

    $scope.imagePath = function (image) {
        if (image === "") {
            return IMAGE_URL + "app/local/modules/Cabride/resources/design/desktop/flat/images/car-icon.png";
        }
        return IMAGE_URL + "images/application" + image;
    };

    $scope.showFixedPricing = function () {
        return $scope.pricingMode === "fixed";
    };

    $scope.refresh = function () {
        $scope.loadPage();
    };

    $scope.loadPage();
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
                                                         SB, $timeout) {
    angular.extend($scope, {
        isOnline: false,
        customer: null,
        information: null,
        isLoggedIn: Customer.isLoggedIn(),
        isPassenger: false,
        isDriver: false,
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
            case "my-rides":
                $state.go("cabride-my-rides");
                break;
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
    .then(function (customer) {
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
            .then(function (customer) {
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
        $scope.isDriver = false;
        $scope.isPassenger = false;
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

    $rootScope.$on('cabride.aggregateInformation', function (event, data) {
        $timeout(function () {
            $scope.information = data.information;
        });
    });

    $rootScope.$on("cabride.setIsOnline", function (event, isOnline) {
        $scope.isOnline = isOnline;
    });

    $rootScope.$on("cabride.isPassenger", function () {
        $scope.isPassenger = true;
        $scope.isDriver = false;
        $scope.rebuildMenu();
    });

    $rootScope.$on("cabride.isDriver", function () {
        $scope.isPassenger = false;
        $scope.isDriver = true;
        $scope.rebuildMenu();
    });

});

