/**
 * Cabride version 2 controllers
 */
angular.module('starter')
.controller('CabrideHome', function ($window, $state, $scope, $rootScope, $timeout, $translate,
                                     $ionicSideMenuDelegate, $q, Modal, Cabride, CabrideUtils, Customer,
                                     Loader, GoogleMaps, Dialog, Location, SB, Places, CabrideBase, PaymentMethod) {
    angular.extend($scope, CabrideBase, {
        pageTitle: Cabride.settings.pageTitle,
        valueId: Cabride.getValueId(),
        isAlive: Cabride.isAlive,
        isLoggedIn: Customer.isLoggedIn(),
        isLoading: true,
        customer: null,
        crMap: null,
        crMapPin: null,
        showMapPin: true,
        showInfoWindow: false,
        currentPlace: null,
        driverMarkers: [],
        gmapsAutocompleteOptions: {},
        ride: {
            seats: '1',
            type: 'course',
            isSearching: false,
            pickupPlace: null,
            pickupAddress: "",
            dropoffPlace: null,
            dropoffAddress: "",
            distance: null,
            duration: 15,
            durationText: "15mn",
            isCourse: false,
            isTour: false
        },
        currentRoute: null,
        isPassenger: false,
        isDriver: false,
        locationIsEnabled: Location.isEnabled,
        removeSideMenu: null
    });

    $rootScope.$on('cabride.isAlive', function () {
        $timeout(function () {
            $scope.isAlive = true;
        });
    });

    $rootScope.$on('cabride.isGone', function () {
        $timeout(function () {
            $scope.isAlive = false;
        });
    });

    $rootScope.$on('cabride.isOnline', function (event, isOnline) {
        $scope.isOnline = isOnline;
        try {
            if (isOnline) {
                window.plugins.insomnia.keepAwake();
            } else {
                window.plugins.insomnia.allowSleepAgain();
            }
        } catch (e) {
            // Silently fails.
        }
    });

    $rootScope.$on('cabride.advertDrivers', function (event, payload) {
        // Refresh driver markers
        $scope.drawDrivers(payload.drivers);
    });

    $rootScope.$on('location.isEnabled', function (event, payload) {
        // Refresh driver markers
        $timeout(function () {
            $scope.locationIsEnabled = payload;
        });
    });

    $scope.$on('$ionicView.enter', function () {
        $ionicSideMenuDelegate.canDragContent(false);
    });

    $scope.$on('$ionicView.afterLeave', function () {
        $ionicSideMenuDelegate.canDragContent(true);
    });


    // Init contextual menu for initial triggers!
    CabrideUtils.rebuildContextualMenu();

    // Passenger / Driver choice!
    $scope.selectPassenger = function () {
        // Check if the user is logged in
        if (!Customer.isLoggedIn()) {
            Customer.loginModal($scope,
                /** Login */
                function () {
                    Loader.show();
                    // Check if it's a driver first!
                    Cabride
                    .fetchUser()
                    .then(function (payload) {
                        $rootScope.$broadcast("cabride.updateUser", payload.user);
                        switch (payload.user.type) {
                            case "driver":
                                $scope.setIsDriver(false);
                                $rootScope.$broadcast("cabride.setIsOnline", payload.user.isOnline);
                                $rootScope.$broadcast("cabride.isOnline", payload.user.isOnline);
                                break;
                            case "passenger":
                            case "new":
                            default:
                                $scope.setIsPassenger(true);
                        }

                        Loader.hide();
                    }).catch(function () {
                        Loader.hide();
                    });
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

    $scope.initMap = function (placesConfig) {
        $scope.mapSettings = {
            zoom: 10,
            center: {
                lat: Cabride.settings.defaultLat,
                lng: Cabride.settings.defaultLng
            },
            disableDefaultUI: true
        };

        $scope.crMap = GoogleMaps.createMap('crMap', $scope.mapSettings);

        // Center on user location if default is blank!
        if (Cabride.settings.defaultLat === 0 &&
            Cabride.settings.defaultLng === 0) {
            $timeout(function () {
                $scope.centerMe();
            }, 500);
        }

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

        if (placesConfig !== undefined) {
            if (placesConfig.markers && placesConfig.markers.constructor === Array) {
                for (var i = 0; i < placesConfig.markers.length; i++) {
                    var marker = placesConfig.markers[i];
                    GoogleMaps.addMarker(marker, i);
                }
            }
        }

        google.maps.event.addListener($scope.crMap, "center_changed", function () {
            // 0.5 seconds after the center of the map has changed,
            // set back the marker position.
            $timeout(function () {
                var center = $scope.crMap.getCenter();
                $scope.crMapPin.setPosition(center);
            }, 500);
        });

        $scope.clearSearch(false);
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
            var b = new google.maps.LatLng(driver.previous.latitude, driver.previous.longitude);
            var heading = google.maps.geometry.spherical.computeHeading(myLatlng, b);

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
                "OK",
                -1,
                "cabride");
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
    $scope.clearSearch = function (withRoute) {
        $scope.ride = {
            seats: Cabride.settings.seatsDefault.toString(),
            type: 'course',
            isSearching: false,
            pickupPlace: null,
            pickupAddress: "",
            dropoffPlace: null,
            dropoffAddress: "",
            distance: null,
            duration: 15,
            durationText: "15mn",
            isCourse: false,
            isTour: false
        };

        if (withRoute) {
            CabrideUtils.clearRoute();
        }
    };

    $scope.disableTap = function (inputId) {
        $scope.showMapPin = false;

        var input = document.getElementsByClassName("pac-container");
        // disable ionic data tab
        angular.element(input).attr("data-tap-disabled", "true");
        // leave input field if google-address-entry is selected
        angular.element(input).on("click", function () {
            document.getElementById(inputId).blur();
        });
    };

    $scope.displayMapPin = function () {
        $scope.showMapPin = true;
    };

    $scope.displayClock = function () {
        return $scope.tourEnabled() && $scope.ride.dropoffAddress === '';
    };

    $scope.decreaseSeats = function () {
        if (parseInt($scope.ride.seats) <= 1) {
            return;
        }
        $timeout(function () {
            $scope.ride.seats = parseInt($scope.ride.seats) - 1;
        });
    };

    $scope.increaseSeats = function () {
        $timeout(function () {
            $scope.ride.seats = parseInt($scope.ride.seats) + 1;
        });
    };

    $scope.decreaseClock = function () {
        if ($scope.ride.duration <= 15) {
            return;
        }
        $timeout(function () {
            $scope.ride.duration -= 15;
            $scope.ride.durationText = CabrideUtils.toHmm($scope.ride.duration * 60);
        });
    };

    $scope.increaseClock = function () {
        if ($scope.ride.duration >= 720) {
            return;
        }
        $timeout(function () {
            $scope.ride.duration += 15;
            $scope.ride.durationText = CabrideUtils.toHmm($scope.ride.duration * 60);
        });
    };

    $scope.pinIcon = function () {
        if ($scope.isDriver && $scope.isOnline) {
            return "./features/cabride/assets/templates/images/003-pin-green.svg";
        }

        return "./features/cabride/assets/templates/images/003-pin.svg";
    };

    $scope.pinText = function () {
        if ($scope.ride.pickupAddress === "") {
            return {
                action: "pickup",
                class: "positive",
                text: $translate.instant("Set pick-up location", "cabride")
            };
        }
        if (!$scope.ride.isTour && $scope.ride.dropoffAddress === "") {
            return {
                action: "dropoff",
                class: "energized",
                text: $translate.instant("Set drop-off location", "cabride")
            };
        }
        if ($scope.ride.isSearching) {
            return {
                action: "loading",
                class: "ng-hide",
                text: ""
            };
        }
        if ($scope.ride.pickupAddress !== "" && $scope.ride.dropoffAddress !== "") {
            return {
                action: "search",
                class: "balanced",
                text: $translate.instant("Request a driver", "cabride")
            };
        }
        return {
            action: "none",
            class: "ng-hide",
            text: ""
        };
    };

    // Starts a tour ride!
    $scope.setTour = function () {
        $scope.requestTour();
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
                        "Location",
                        "Your position doesn't resolve to a valid address.",
                        "OK",
                        -1,
                        "cabride");
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
                        "Location",
                        "Your position doesn't resolve to a valid address.",
                        "OK",
                        -1,
                        "cabride");
                });
                break;
            case "search":
                $scope.requestRide();

                break;
            case "none":
            default:
                break;
        }
    };

    $scope.checkSeats = function () {
        if (!Cabride.settings.enableSeats) {
            return true;
        }
        if ($scope.ride.seats < 1) {
            Dialog.alert('Seats', 'Please select the number of seats you need!', 'OK', -1, 'cabride');
            return false;
        }
        return true;
    };

    // Tour
    $scope.vaults = null;
    $scope.requestTour = function () {
        // Break if seats are required & not set
        if (!$scope.checkSeats()) {
            return;
        }

        $scope.ride.isSearching = true;
        $scope.ride.isTour = true;
        $scope.ride.type = 'tour';

        var pickup = {
            latitude: $scope.ride.pickupPlace.geometry.location.lat(),
            longitude: $scope.ride.pickupPlace.geometry.location.lng(),
        };

        $scope.ride.pickup = pickup;

        Cabride
            .requestTour($scope.ride)
            .then(function (response) {
                if (response.collection && Object.keys(response.collection).length > 0) {
                    $scope.vaults = response.vaults;
                    $scope.showModal(response.collection);
                } else {
                    Dialog.alert("", "We are sorry we didnt found any available driver around you!", "OK", -1, "cabride");
                }
            }, function (error) {
                Dialog.alert("", "We are sorry we didnt found any available driver around you!", "OK", -1, "cabride");
            }).then(function () {
                $scope.ride.isSearching = false;
                $scope.ride.isTour = false;
            });
    };

    $scope.vaults = null;
    $scope.requestRide = function () {
        // Break if seats are required & not set
        if (!$scope.checkSeats()) {
            return;
        }

        $scope.ride.isSearching = true;
        $scope.ride.isCourse = true;
        $scope.ride.type = 'course';

        Cabride
            .requestRide($scope.currentRoute, $scope.ride)
            .then(function (response) {
                if (response.collection && Object.keys(response.collection).length > 0) {
                    $scope.vaults = response.vaults;
                    $scope.showModal(response.collection);
                } else {
                    Dialog.alert("", "We are sorry we didnt found any available driver around you!", "OK", -1, "cabride");
                }
            }, function (error) {
                Dialog.alert("", "We are sorry we didnt found any available driver around you!", "OK", -1, "cabride");
            }).then(function () {
                $scope.ride.isSearching = false;
                $scope.ride.isCourse = false;
            });
    };

    $scope.vtModal = null;
    $scope.showModal = function (vehicles) {
        Modal
        .fromTemplateUrl("features/cabride/assets/templates/l1/modal/vehicle-type.html", {
            scope: angular.extend($scope.$new(true), {
                close: function () {
                    $scope.vtModal.remove();
                },
                selectVehicle: function (vehicleType) {
                    $scope.selectVehicle(vehicleType);
                },
                ride: $scope.ride,
                vehicles: vehicles
            }),
            animation: "slide-in-right-left"
        }).then(function (modal) {
            $scope.vtModal = modal;
            $scope.vtModal.show();

            return modal;
        });
    };

    $scope.selectVehicle = function (vehicleType) {
        // Payment modal
        $scope.vehicleType = vehicleType;
        $scope.paymentTypeModal();
    };

    $scope.textRecap = function () {
        var recap = [];
        if ($scope.ride.type === 'course') {
            recap.push('<div class="item item-divider item-divider-custom text-center">' + $translate.instant('Course', 'cabride') + '</div>');
            recap.push('<div class="item item-custom">');
            if (Cabride.settings.enableSeats) {
                recap.push('    <span class="text-center">- ' + $scope.ride.seats + ' ' + ($scope.ride.seats === 1 ? $translate.instant('seat', 'cabride') : $translate.instant('seats', 'cabride')) + '</span><br />');
            }
            recap.push('    <span class="text-center">- ' + $scope.ride.pickupAddress + '</span><br />');
            recap.push('    <span class="text-center">- ' + $scope.ride.dropoffAddress + '</span><br />');
            recap.push('</div>');
            recap.push('<div style="margin-bottom: 20px;"></div>');
        }

        if ($scope.ride.type === 'tour') {
            recap.push('<div class="item item-divider item-divider-custom text-center">' + $translate.instant('Tour', 'cabride') + '</div>');
            recap.push('<div class="item item-custom">');
            if (Cabride.settings.enableSeats) {
                recap.push('    <span class="text-center">- ' + $scope.ride.seats + ' ' + ($scope.ride.seats === 1 ? $translate.instant('seat', 'cabride') : $translate.instant('seats', 'cabride')) + '</span><br />');
            }
            recap.push('    <span class="text-center">- ' + $scope.ride.durationText + '</span><br />');
            recap.push('    <span class="text-center">- ' + $scope.ride.pickupAddress + '</span><br />');
            recap.push('</div>');
            recap.push('<div style="margin-bottom: 20px;"></div>');
        }

        return recap.join('');
    };

    $scope.paymentTypeModal = function (paymentTypes) {

        console.log('paymentTypes', paymentTypes);

        PaymentMethod.openModal($scope, {
            title: $translate.instant('Select a payment method', 'payment_demo'),
            type: PaymentMethod.AUTHORIZATION,
            display: {
                amount: false,
                recap: true
            },
            labels: {
                amount: $translate.instant('Amount', 'payment_demo'),
                amountExtra: $translate.instant('This is a pre-authorization, you will only be charged after the ride is completed.', 'payment_demo'),
                authorizeLoaderMessage: $translate.instant('Authorizing payment...', 'payment_demo')
            },
            methods: Cabride.settings.paymentGateways,
            elementsStyle: {
                base: {
                    color: "#32325d",
                    fontFamily: "'Helvetica Neue', Helvetica, sans-serif",
                    fontSmoothing: "antialiased",
                    fontSize: "16px",
                    "::placeholder": {
                        color: "#aab7c4"
                    }
                },
                invalid: {
                    color: "#fa755a",
                    iconColor: "#fa755a"
                }
            },
            enableVaults: true,
            payment: {
                currency: Cabride.settings.currency.code,
                amount: $scope.vehicleType.pricingValue,
                formattedAmount: $scope.vehicleType.pricing,
                recap: $scope.textRecap()
            },
            actions: [
                PaymentMethod.ACTION_AUTHORIZE
            ],
            valueId: Cabride.getValueId(),
            settings: Cabride.settings,
            onSelect: function (options) {
                console.log('onSelect', options);
            },
            onSuccess: function (options) {
                console.log('onSuccess', options);
                $scope.validateRequest(options.paymentId);
            },
            onError: function (options) {
                console.log('onError', options);
            }
        });
    };

    $scope.selectVehicle = function (vehicleType) {
        // Payment modal
        $scope.vehicleType = vehicleType;
        $scope.paymentTypeModal();
    };

    $scope.validateRequest = function (paymentId) {
        Loader.show($translate.instant("Sending request ...", "cabride"));
        Cabride
        .validateRequest($scope.vehicleType, $scope.currentRoute, $scope.ride, paymentId, Cabride.settings.customFormFieldsUser)
        .then(function (response) {
            Loader.hide();
            Dialog
            .alert('Request sent', 'Please now wait for a driver!', 'OK', 2350, 'cabride')
            .then(function () {
                PaymentMethod.closeModal();
                $scope.vtModal.hide();
                $state.go('cabride-my-rides');
            });
            // Clear ride
            $scope.clearSearch(true);
        }, function (error) {
            Loader.hide();
            Dialog
            .alert('Sorry!', error.message, 'OK')
            .then(function () {
                PaymentMethod.closeModal();
                $scope.vtModal.hide();
                $state.go('cabride-my-rides');
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
                    "Location",
                    "Your position doesn't resolve to a valid address.",
                    "OK", -1, "cabride");
            })
        }, function () {
            Dialog.alert(
                "Location",
                "Sorry we are unable to locate you, please check your GPS settings & authorization.",
                "OK", -1, "cabride");
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
                    "Location",
                    "Your position doesn't resolve to a valid address.",
                    "OK", -1, "cabride");
            });
        }, function () { // Error!
            Dialog.alert(
                "Location",
                "Sorry we are unable to locate you, please check your GPS settings & authorization.",
                "OK", -1, "cabride");
        });
    };

    $scope.rebuild = function () {
        $scope
            .loadPlacesPOI()
            .then(function (success) {
                $scope.initMap(success);
            }, function (error) {
                $scope.initMap();
            });

    };

    $scope.hideInfoWindow = function () {
        $scope.showInfoWindow = false;
    };

    $scope.goToPlace = function (placeId) {
        $state.go('places-view', {
            value_id: Cabride.settings.placesValueId,
            page_id: placeId
        });
    };

    $scope.loadPlacesPOI = function () {
        var deferred = $q.defer();
        if (Cabride.settings.placesValueId !== 0) {
            Places.setValueId(Cabride.settings.placesValueId);
            Places
                .findAllMaps({}, false)
                .then(function (data) {
                    $scope.collection = data.places;

                    Places.mapCollection = $scope.collection;

                    var markers = [];

                    for (var i = 0; i < $scope.collection.length; i = i + 1) {
                        var place = $scope.collection[i];
                        var marker = {
                            config: {
                                id: angular.copy(place.id),
                                place: angular.copy(place)
                            },
                            onClick: (function (marker) {
                                $timeout(function () {
                                    if (Places.settings.mapAction &&
                                        Places.settings.mapAction === 'gotoPlace') {
                                        $scope.goToPlace(marker.config.place.id);
                                    } else {
                                        $scope.showInfoWindow = true;
                                        $scope.currentPlace = marker.config.place;
                                    }
                                });
                            })
                        };

                        if (place.address.latitude && place.address.longitude) {
                            marker.latitude = place.address.latitude;
                            marker.longitude = place.address.longitude;
                        } else {
                            marker.address = place.address.address;
                        }

                        switch (place.mapIcon) {
                            case "pin":
                                if (place.pin) {
                                    marker.icon = {
                                        url: place.pin,
                                        width: 42,
                                        height: 42
                                    };
                                }
                                break;
                            case "image":
                                if (place.picture) {
                                    marker.icon = {
                                        url: place.picture,
                                        width: 70,
                                        height: 44
                                    };
                                }
                                break;
                            case "thumbnail":
                                if (place.thumbnail) {
                                    marker.icon = {
                                        url: place.thumbnail,
                                        width: 42,
                                        height: 42
                                    };
                                }
                                break;
                            case "default": default:
                                // Defaults to google map icons
                                break;
                        }

                        markers.push(marker);
                    }

                    $scope.map_config = {
                        //cluster: true,
                        markers: markers,
                        //bounds_to_marker: true
                    };
                    deferred.resolve($scope.map_config);
                }, function (error) {
                    deferred.reject();
                });
        } else {
            deferred.reject();
        }

        return deferred.promise;
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
            // If logged-in, do not force login at startup anymore!
            if (Customer.isLoggedIn()) {
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
                        $rootScope.$broadcast("cabride.updateUser", payload.user);
                        switch (payload.user.type) {
                            case "driver":
                                $scope.setIsDriver(false);
                                $rootScope.$broadcast("cabride.setIsOnline", payload.user.isOnline);
                                $rootScope.$broadcast("cabride.isOnline", payload.user.isOnline);
                                break;
                            case "passenger":
                                $scope.setIsPassenger(false);
                                break;
                            case "new":
                            default:
                                if (!Cabride.settings.driverCanRegister) {
                                    $scope.selectPassenger();
                                }
                        }

                        $scope.isLoading = false;
                    });
                });
            } else {
                $scope.isLoading = false;
            }
        }, function () {
            $scope.isLoading = false;
        }).catch(function () {
            $scope.isLoading = false;
        });
    };

    $scope.init();

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
        if (Customer.isLoggedIn()) {
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
                    $rootScope.$broadcast("cabride.updateUser", payload.user);
                    switch (payload.user.type) {
                        case "driver":
                            $scope.setIsDriver(false);
                            $rootScope.$broadcast("cabride.setIsOnline", payload.user.isOnline);
                            break;
                        case "passenger":
                            $scope.setIsPassenger(false);
                            break;
                        case "new":
                        default:
                            if (!Cabride.settings.driverCanRegister) {
                                $scope.selectPassenger();
                            }
                    }

                    $scope.isLoading = false;
                });
            });
        }
    });

    $rootScope.$on(SB.EVENTS.AUTH.registerSuccess, function () {
        $scope.init();
    });

    $rootScope.$on(SB.EVENTS.AUTH.editSuccess, function () {
        $scope.init();
    });

    // Action on state-name! shortcuts for passenger/driver signup
    if (!Customer.isLoggedIn()) {
        var currentState = $state.current.name;
        if (currentState === "cabride-signup-passenger") {
            $scope.selectPassenger();
        }
        if (currentState === "cabride-signup-driver") {
            $scope.selectDriver();
        }
    }
});

