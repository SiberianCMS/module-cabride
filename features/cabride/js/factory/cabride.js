/**
 * Cabride factory
 */
angular.module('starter')
    .factory('Cabride', function (CabrideSocket, CabridePayment, Customer, Application, Pages, Modal, Location, SB,
                                  $q, $session, $rootScope, $interval, $timeout, $log, $ionicPlatform, ContextualMenu,
                                  $pwaRequest, PushService, Push, Dialog, Loader, $state, $ionicSideMenuDelegate) {
        var factory = {
            value_id: null,
            settings: {
                avatarProvider: 'identicon'
            },
            isAlive: false,
            socket: null,
            uuid: null,
            waitPong: false,
            initPromise: false,
            helloPromise: $q.defer(),
            lobbyPromise: null,
            joinedLobby: false,
            awaitPromises: [],
            rooms: [],
            publicRooms: [],
            privateRooms: [],
            /** Settings */
            isPassenger: false,
            isDriver: false,
            isTaxiLayout: false,
            /** Promises */
            updatePositionPromise: null,
        };

        factory.setValueId = function (valueId) {
            factory.value_id = valueId;

            return factory;
        };

        factory.getValueId = function () {
            return factory.value_id;
        };

        factory.onStart = function () {
            Application.loaded.then(function () {
                // App runtime!
                var cabride = _.find(Pages.getActivePages(), {
                    code: 'cabride'
                });

                // Module is not in the App!
                if (!cabride) {
                    return;
                }

                factory
                    .setValueId(cabride.value_id)
                    .init()
                    .then(function (success) {
                        // Debug connected users & rooms
                        setInterval(function () {
                            factory
                                .ping(true).promise
                                .then(function () {
                                    factory.setIsAlive();
                                }).catch(function () {
                                    factory.setIsGone();
                                });
                        }, 60000);
                    }).catch(function (error) {
                        console.log('cabride error', error);
                    });
            });
        };

        // Handle the known protocols
        factory.onMessage = function (message) {
            switch (message.event) {
                case 'hello':
                    if (message.uuid !== '') {
                        factory.sendEvent('hello');
                        factory.helloPromise.resolve(message);
                    } else {
                        factory.helloPromise.reject({
                            message: 'Empty or Invalid UUID!'
                        });
                    }
                    break;
                case 'ack': // Acknowledgments
                    break;
                case 'request-ok': // Acknowledgments
                    break;
                case 'request-ko': // Acknowledgments
                    break;
                case 'advert-drivers':
                    $rootScope.$broadcast('cabride.advertDrivers', {drivers: message.drivers});
                    break;
                case 'aggregate-information':
                    $rootScope.$broadcast('cabride.aggregateInformation', {information: message.information});
                    break;
                case 'ping': // Ping!
                    factory.sendEvent('pong');
                    break;
                case 'pong': // Pong!
                    factory.waitPong = false;
                    break;
                case 'joinlobby-ok':
                    if (factory.lobbyPromise !== null) {
                        factory.lobbyPromise.resolve();
                    }
                    factory.joinedLobby = true;
                    break;
                // Generally this case won't happen so much, but if it does, we can cleanly closes the connection
                case 'closing-websocket':
                    // close from here too!
                    break;
            }
        };

        factory.getMyRides = function () {
            return $pwaRequest.post('/cabride/mobile_ride/me', {
                urlParams: {
                    value_id: this.value_id
                },
                cache: false
            });
        };

        factory.getMyPayments = function () {
            return $pwaRequest.post('/cabride/mobile_ride/my-payments', {
                urlParams: {
                    value_id: this.value_id
                },
                cache: false
            });
        };

        factory.getPendingRides = function () {
            return $pwaRequest.post('/cabride/mobile_ride/pending', {
                urlParams: {
                    value_id: this.value_id
                },
                cache: false
            });
        };

        factory.getAcceptedRides = function () {
            return $pwaRequest.post('/cabride/mobile_ride/accepted', {
                urlParams: {
                    value_id: this.value_id
                },
                cache: false
            });
        };

        factory.getCompletedRides = function () {
            return $pwaRequest.post('/cabride/mobile_ride/completed', {
                urlParams: {
                    value_id: this.value_id
                },
                cache: false
            });
        };

        factory.getCancelledRides = function () {
            return $pwaRequest.post('/cabride/mobile_ride/cancelled', {
                urlParams: {
                    value_id: this.value_id
                },
                cache: false
            });
        };

        factory.declineRide = function (requestId) {
            return $pwaRequest.post('/cabride/mobile_ride/decline', {
                urlParams: {
                    value_id: this.value_id,
                    requestId: requestId
                },
                cache: false
            });
        };

        factory.acceptRide = function (requestId, route) {
            return $pwaRequest.post('/cabride/mobile_ride/accept', {
                urlParams: {
                    value_id: this.value_id,
                    requestId: requestId
                },
                data: {
                    route: route
                },
                cache: false
            });
        };

        factory.cancelRide = function (requestId) {
            return $pwaRequest.post('/cabride/mobile_ride/cancel', {
                urlParams: {
                    value_id: this.value_id,
                    requestId: requestId
                },
                cache: false
            });
        };

        factory.driveToPassenger = function (requestId, route) {
            return $pwaRequest.post('/cabride/mobile_ride/drive-to-passenger', {
                urlParams: {
                    value_id: this.value_id,
                    requestId: requestId
                },
                data: {
                    route: route
                },
                cache: false
            });
        };

        factory.driveToDestination = function (requestId) {
            return $pwaRequest.post('/cabride/mobile_ride/drive-to-destination', {
                urlParams: {
                    value_id: this.value_id,
                    requestId: requestId
                },
                cache: false
            });
        };

        factory.completeRide = function (requestId) {
            return $pwaRequest.post('/cabride/mobile_ride/complete', {
                urlParams: {
                    value_id: this.value_id,
                    requestId: requestId
                },
                cache: false
            });
        };

        factory.getVehicleInformation = function () {
            return $pwaRequest.post('/cabride/mobile_ride/vehicle-information', {
                urlParams: {
                    value_id: this.value_id,
                },
                cache: false
            });
        };

        factory.selectVehicleType = function (typeId) {
            return $pwaRequest.post('/cabride/mobile_ride/select-vehicle-type', {
                urlParams: {
                    value_id: this.value_id,
                    typeId: typeId
                },
                cache: false
            });
        };

        factory.saveDriver = function (driver) {
            return $pwaRequest.post('/cabride/mobile_ride/save-driver', {
                urlParams: {
                    value_id: this.value_id,
                },
                data: {
                    driver: driver,
                },
                cache: false
            });
        };

        factory.saveCard = function (card, type) {
            return $pwaRequest.post('/cabride/mobile_payment/save-card', {
                urlParams: {
                    value_id: this.value_id,
                },
                data: {
                    card: card,
                    type: type
                },
                cache: false
            });
        };

        factory.deleteVault = function (vault) {
            return $pwaRequest.post('/cabride/mobile_payment/delete-vault', {
                urlParams: {
                    value_id: this.value_id,
                    vaultId: vault.client_vault_id
                },
                cache: false
            });
        };

        factory.onError = function (message) {
            $log.error('cabride socket onerror', message);
        };

        factory.sendEvent = function (eventType, payload) {
            var localPayload = angular.extend({
                event: eventType,
                uuid: factory.uuid
            }, payload);

            CabrideSocket.sendMsg(localPayload);
        };

        factory.requestRide = function (route) {
            return $pwaRequest.post('/cabride/mobile_request/ride', {
                urlParams: {
                    value_id: this.value_id
                },
                data: {
                    route: route
                },
                cache: false
            });
        };

        factory.validateRequest = function (vehicleType, route, cashOrVault) {
            return $pwaRequest.post('/cabride/mobile_request/validate', {
                urlParams: {
                    value_id: this.value_id
                },
                data: {
                    vehicleType: vehicleType,
                    cashOrVault: cashOrVault,
                    route: route
                },
                cache: false
            });
        };

        factory.fetchRequest = function (requestId) {
            return $pwaRequest.get('/cabride/mobile_request/fetch', {
                urlParams: {
                    value_id: this.value_id,
                    requestId: requestId
                },
                cache: false
            });
        };

        factory.rdModal = null;
        factory.requestDetailsModal = function (newScope, requestId, userType) {
            Loader.show();

            factory
            .fetchRequest(requestId)
            .then(function (payload) {
                Modal
                .fromTemplateUrl("features/cabride/assets/templates/l1/modal/request-details.html", {
                    scope: angular.extend(newScope, {
                        close: function () {
                            factory.rdModal.hide();
                        },
                        request: payload.request,
                        userType: userType
                    }),
                    animation: 'slide-in-up'
                }).then(function (modal) {
                    factory.rdModal = modal;
                    factory.rdModal.show();

                    return modal;
                });
            }).then(function () {
                Loader.hide();
            });
        };

        /**
         * Short aliases
         */
        factory.updatePosition = function () {
            if (factory.joinedLobby === false) {
                return;
            }
            Location
            .getLocation()
            .then(function (position) {
                factory.lastPosition = position.coords;
                factory.sendEvent('update-position', {
                    userId: Customer.customer.id,
                    userType: factory.isDriver ? 'driver' : 'passenger',
                    position: {
                        latitude: position.coords.latitude,
                        longitude: position.coords.longitude
                    }
                });
            }, function () {
                // Skipping this time!
            });
        };

        factory.startUpdatePosition = function () {
            // Ensure we start only once the position poller!
            factory.updatePosition();
            if (factory.updatePositionPromise === null) {
                factory.updatePositionPromise = $interval(function () {
                    factory.updatePosition();
                }, 15000);
            }
        };

        factory.stopUpdatePosition = function () {
            // Stops only of started/promise exists!
            if (factory.updatePositionPromise !== null) {
                $interval.cancel(factory.updatePositionPromise);
                factory.updatePositionPromise = null;
            }
        };

        /**
         * Ping every minute!
         */
        factory.ping = function (retry, previousPromise) {
            var pingPromise = (previousPromise === undefined) ?
                $q.defer() : previousPromise;

            factory.sendEvent('ping', {});
            factory.waitPong = true;

            $timeout(function () {
                if (factory.waitPong === true) {
                    if (retry) {
                        factory.ping(false, pingPromise);
                    } else {
                        pingPromise.reject();
                    }
                } else {
                    pingPromise.resolve();
                }
            }, 60000);

            return pingPromise;
        };

        /**
         * Confirm server is alive
         */
        factory.setIsAlive = function () {
            $rootScope.$broadcast('cabride.isAlive');
            factory.isAlive = true;
        };

        /**
         * Confirm server is gone
         */
        factory.setIsGone = function () {
            $rootScope.$broadcast('cabride.isGone');
            factory.isAlive = false;
        };

        /**
         * Set user as Passenger
         */
        factory.setIsPassenger = function (update) {
            $rootScope.$broadcast('cabride.isPassenger');
            factory.isPassenger = true;
            factory.isDriver = false;

            // Update in DB!
            if (update === true) {
                factory.updateUser("passenger");
            }
        };

        /**
         * Set user as Passenger
         */
        factory.setIsDriver = function (update) {
            $rootScope.$broadcast('cabride.isDriver');
            factory.isPassenger = false;
            factory.isDriver = true;

            // Update in DB!
            if (update === true) {
                factory.updateUser("driver");
            }
        };

        /**
         * Fetch user
         */
        factory.fetchUser = function () {
            factory.updateUserPush();
            return $pwaRequest.post('/cabride/mobile_view/fetch-user', {
                urlParams: {
                    value_id: this.value_id
                },
                cache: false
            });
        };

        /**
         * Ensure user is registered for pushes!
         */
        factory.updateUserPush = function () {
            PushService
                .isReadyPromise
                .then(function () {
                    $pwaRequest.post('/cabride/mobile_view/update-user-push', {
                        urlParams: {
                            value_id: this.value_id,
                            device: Push.device_type,
                            token: Push.device_token
                        },
                        cache: false
                    });
                }, function () {
                    $log.info("[Ride] not registering user device for push.");
                });
        };

        /**
         * Update user
         */
        factory.updateUser = function (userType) {
            return $pwaRequest.post('/cabride/mobile_view/update-user', {
                urlParams: {
                    value_id: this.value_id,
                    userType: userType
                },
                cache: false
            });
        };

        /**
         * Update user
         */
        factory.toggleOnlineStatus = function (isOnline) {
            return $pwaRequest.post('/cabride/mobile_view/toggle-online', {
                urlParams: {
                    value_id: this.value_id,
                    isOnline: isOnline
                },
                cache: false
            });
        };

        /**
         *
         * @param isTaxiLayout
         */
        factory.setIsTaxiLayout = function (isTaxiLayout) {
            factory.isTaxiLayout = isTaxiLayout;

            // Clear ContextualMenu
            ContextualMenu.reset();
        };

        /**
         * Short aliases
         *
         * @param payload
         */
        factory.sendMessage = function (payload) {
            var localPayload = angular.extend({
                messageId: Date.now() * 1000000, // To nanoseconds (only for instant sorting)
                userId: Customer.customer.id
            }, payload);
            factory.sendEvent('request', localPayload);
        };

        /**
         *
         * @param sbToken
         * @param appKey
         */
        factory.joinLobby = function (sbToken, appKey) {
            var deferred = $q.defer();
            if (factory.lobbyPromise === null) {
                factory.sendEvent('join-lobby', {
                    sbToken: sbToken,
                    appKey: appKey,
                    valueId: factory.value_id
                });
                factory.lobbyPromise = deferred;
            } else {
                $log.info('You already joined the lobby!');
                deferred.resolve();
            }
            return deferred.promise;
        };

        /**
         * Fetch wss & feature settings
         */
        factory.fetchSettings = function () {
            return $pwaRequest.post('/cabride/mobile_view/fetch-settings', {
                urlParams: {
                    value_id: this.value_id
                },
                cache: false
            });
        };

        /**
         * Reconnects & Join back rooms
         */
        factory.reconnect = function () {
            factory
                .init()
                .then(function () {
                    // @todo join driver/passenger rooms
                });
        };

        /**
         * Initializes the cabride connection
         *
         * @return Promise
         */
        factory.init = function () {
            // Customer must be logged in for Taxi socket to connect!
            if (!Customer.isLoggedIn()) {
                return $q.reject();
            }
            if (factory.isAlive) {
                return $q.resolve();
            }

            if (factory.initPromise === false) {
                factory.initPromise = $q.defer();
            } else {
                return factory.initPromise.promise;
            }

            CabridePayment.init();

            factory
                .fetchSettings()
                .then(function (response) {
                    factory.settings = angular.extend({}, factory.settings, response.settings);
                    factory.socket = CabrideSocket.connect(
                        response.settings.wssUrl,
                        factory.onMessage,
                        factory.onError);

                    factory.socket = CabrideSocket.socket;

                    factory.socket.onclose = function (event) {
                        factory.setIsGone();
                    };

                    factory.socket.onopen = function (event) {
                        factory.helloPromise.promise
                        .then(function (helloResponse) {
                            factory.uuid = helloResponse.uuid;
                            factory.setIsAlive();
                            factory.joinLobby($session.getId(), APP_KEY)
                            .then(function () {
                                factory.initPromise.resolve();

                                // Send position updates to the server!
                                factory.startUpdatePosition();
                            }).catch(function (error) {
                                factory.initPromise.reject(error);
                            }).finally(function () {
                                $log.info('cabride joinLobby finally');
                            });
                        }).catch(function (error) {
                            $log.info('cabride helloPromise error', error);
                            factory.initPromise.reject(error);
                        }).finally(function () {
                            $log.info('cabride helloPromise finally');
                        });
                    };
                }).catch(function (error) {
                    factory.initPromise.reject(error);
                });

            return factory.initPromise.promise;
        };

        $ionicPlatform.on('resume', function () {
            if (DEVICE_TYPE === SB.DEVICE.TYPE_BROWSER) {
                factory.startUpdatePosition();
            }
        });

        $ionicPlatform.on('pause', function () {
            if (DEVICE_TYPE === SB.DEVICE.TYPE_BROWSER) {
                factory.stopUpdatePosition();
            }
        });

        $rootScope.$on(SB.EVENTS.AUTH.logoutSuccess, function () {
            factory.stopUpdatePosition();
        });

        $rootScope.$on("cabride.isTaxiLayout", function () {
            factory.setIsTaxiLayout(true);
        });

        $rootScope.$on("cabride.isOnline", function (event, isOnline) {
            // Refresh driver markers
            factory
            .toggleOnlineStatus(isOnline)
            .then(function (payload) {
                $rootScope.$broadcast("cabride.setIsOnline", payload.isOnline);
            }, function (error) {
                $rootScope.$broadcast("cabride.setIsOnline", false);
                Dialog
                .alert("Incomplete profile!", error.message, "OK", 5000)
                .then(function () {
                    if ($ionicSideMenuDelegate.isOpenLeft()) {
                        $ionicSideMenuDelegate.toggleLeft();
                    }
                    if ($ionicSideMenuDelegate.isOpenRight()) {
                        $ionicSideMenuDelegate.toggleRight();
                    }
                    $state.go("cabride-vehicle-information");
                });
            });
        });

        return factory;
    });
