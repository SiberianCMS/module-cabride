/**
 * Cabride factory
 */
angular.module('starter')
    .factory('Cabride', function (CabrideSocket, CabridePayment, Customer, Application, Pages, Location, SB,
                                  $q, $session, $rootScope, $interval, $timeout, $log, $ionicPlatform,
                                  $pwaRequest) {
        let factory = {
            value_id: null,
            settings: {
                avatarProvider: 'identicon'
            },
            isAlive: false,
            socket: null,
            uuid: null,
            waitPong: false,
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
            console.log('cabride loaded');
            Application.loaded.then(function () {
                console.log('cabride application loaded');
                // App runtime!

                var cabride = _.find(Pages.getActivePages(), {
                    code: 'cabride'
                });

                factory
                    .setValueId(cabride.value_id)
                    .init()
                    .then(function (success) {
                        console.log('cabride init ok loaded');
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
            $log.info('cabride socket onmessage', message);
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
                    $log.info('CabRide ACK: ', message.message);
                    break;
                case 'request-ok': // Acknowledgments
                    $log.info('CabRide request OK');
                    break;
                case 'request-ko': // Acknowledgments
                    $log.info('CabRide request KO');
                    break;
                case 'advert-drivers':
                    $log.info('CabRide advert-drivers', message.drivers);

                    // Broadcast to map Controller for instant refresh!
                    $rootScope.$broadcast('cabride.advertDrivers', {drivers: message.drivers});
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
                console.log('position', position);
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
            return $pwaRequest.post('/cabride/mobile_view/fetch-user', {
                urlParams: {
                    value_id: this.value_id
                },
                cache: false
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
         *
         * @param isTaxiLayout
         */
        factory.setIsTaxiLayout = function (isTaxiLayout) {
            factory.isTaxiLayout = isTaxiLayout;
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
            if (factory.isAlive) {
                return $q.resolve();
            }

            var deferred = $q.defer();

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
                                deferred.resolve();

                                // Send position updates to the server!
                                factory.startUpdatePosition();
                            }).catch(function (error) {
                                deferred.reject(error);
                            }).finally(function () {
                                $log.info('cabride joinLobby finally');
                            });
                        }).catch(function (error) {
                            $log.info('cabride helloPromise error', error);
                            deferred.reject(error);
                        }).finally(function () {
                            $log.info('cabride helloPromise finally');
                        });
                    };
                }).catch(function (error) {
                    deferred.reject(error);
                });

            return deferred.promise;
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

        $rootScope.$on("cabride.isTaxiLayout", function () {
            factory.setIsTaxiLayout(true);
        });

        return factory;
    });
