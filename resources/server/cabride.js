'use strict';

/**
 * CabRide server based on WebSocket
 *
 * @type {*}
 */

const WebSocketServer = require('ws'),
    request = require('xhr-request'), // Must replace request with axios!
    axios = require('axios'),
    geolib = require('geolib'),
    btoa = require('btoa'),
    atob = require('atob'),
    https = require('https'),
    path = require('path'),
    fs = require('fs'),
    nanoTime = require('nano-time'),
    Deferred = require('native-promise-deferred'),
    uuidv4 = require('uuid/v4'),
    levels = ['info', 'debug', 'warning', 'error', 'exception', 'throw'];

// Saving current PID for termination.
fs.writeFileSync(path.resolve(__dirname, 'server.pid'), process.pid);

let httpsAgent = new https.Agent({
    rejectUnauthorized: false
});

let config = require('./config.json'),
    options = {
        path: '/cabride'
    },
    debug = false,
    defaultUrl = config.apiUrl + '/#APP_KEY#/cabride/api_message',
    apiUrl = null,
    requestDefaultHeaders = {},
    globals = {
        allConnections: [],
        drivers: [],
        passengers: [],
        users: [],
        rooms: []
    };

if (config.auth) {
    switch (config.auth) {
        case 'bearer':
            requestDefaultHeaders = {
                'Api-Auth-Bearer': 'Bearer ' + config.bearer
            };
            break;
        case 'basic':
        default:
            requestDefaultHeaders = {
                'Authorization': 'Basic ' + btoa(config.username + ':' + atob(config.password)),
            };
    }
}

/**
 *
 * @param object
 * @returns {string}
 */
const toMsg = function (object) {
    return JSON.stringify(object);
};

const functions = {
        /**
         * Log function with debug toggler
         */
        log: function () {
            let args = arguments,
                level = 'info',
                log = Function.prototype.bind.call(console.log, console);

            if (levels.indexOf(args[args.length - 1]) !== -1) {
                level = args[args.length - 1];
                delete args[args.length - 1];
            }

            switch (level) {
                case 'exception':
                case 'throw':
                    throw new Error(level + ' >> ' + args.join(', '));
                case 'error':
                case 'warning':
                case 'debug':
                case 'info':
                default:
                    log.apply(console, args);
                    break;
            }
        },
        /**
         * Ping/Pong handler, this will ping client and wait for answer!
         * Will disconnect socket after two failed attempts
         *
         * @param localConnection
         * @param retry
         * @param previousPromise
         */
        pingPong: function (localConnection, retry, previousPromise) {
            let promise = (previousPromise === undefined) ?
                new Deferred() : previousPromise;

            localConnection.waitPong = true;
            localConnection.websocket.send(toMsg({
                event: 'ping'
            }));
            setTimeout(function () {
                if (localConnection.waitPong === true) {
                    if (retry === true) {
                        // Seems the user didn't answered to our ping!
                        // Trying once again
                        functions.pingPong(localConnection, false, promise);
                    } else {
                        // Closing websocket (This message probably will never reach the user)
                        localConnection.websocket.send(toMsg({
                            event: 'closing-websocket'
                        }));

                        localConnection.websocket.close();

                        promise.reject({
                            'error': true,
                            'message': 'No answer to ping!'
                        });
                    }
                } else {
                    promise.resolve({
                        'success': true,
                        'message': 'Ping OK!'
                    });
                }
            }, 3000);
            return promise;
        },
        /**
         * XHR API wrapper for easy requests to API Endpoint
         *
         * @param localConnection
         * @param endpoint
         * @param params
         * @returns {*}
         */
        requestApi: function (localConnection, endpoint, params) {
            let promise = new Deferred();
            let localParams = Object.assign({
                sbToken: localConnection.sbToken,
                valueId: localConnection.valueId
            }, params);
            let uri = apiUrl +
                endpoint +
                '?sb-token=' +
                localParams.sbToken;

            try {
                request(uri, {
                    method: 'POST',
                    json: true,
                    body: localParams,
                    headers: requestDefaultHeaders,
                    responseType: 'json'
                }, function (error, payload, response) {
                    functions.log('requestApi', payload);
                    if (error || response.statusCode === 400) {
                        promise.reject({
                            'error': true,
                            'message': (payload) ?
                                payload.message : 'Unknown API error!'
                        });
                    } else {
                        promise.resolve({
                            'success': true,
                            'payload': payload
                        });
                    }
                });
            } catch (e) {
                promise.reject({
                    'error': true,
                    'message': 'Unknown API error!'
                });
            }

            return promise;
        },
        /**
         * Clean-up all objects related to the lost connection
         * @param localConnection
         */
        clearConnection: function (localConnection) {
            // Close websocket!
            if (!localConnection) {
                return;
            }

            localConnection.websocket.close();

            // Clear user from all rooms!
            let uuid = localConnection.uuid;
            if (Array.isArray(globals.rooms) && globals.rooms.length > 0) {
                for (let key in globals.rooms) {
                    if (key in globals.rooms && uuid in globals.rooms[key]) {
                        delete globals.rooms[key][uuid];
                    }
                }
            }

            // Clear all globals
            if (uuid in globals.users) {
                delete globals.users[uuid];
            }
            if (uuid in globals.drivers) {
                delete globals.drivers[uuid];
            }
            if (uuid in globals.passengers) {
                delete globals.passengers[uuid];
            }
            // Then clear the connection itself
            if (uuid in globals.allConnections) {
                delete globals.allConnections[uuid];
            }
        },
        /**
         * Advertise a new user joining the Chat lobby, filling its user credentials too!
         *
         * @param localConnection
         * @param params
         * @returns {*}
         */
        joinLobby: function (localConnection, params) {
            apiUrl = defaultUrl.replace('#APP_KEY#', params.appKey);

            let joinLobby = functions.requestApi(localConnection, '/join-lobby', {
                'sbToken': params.sbToken
            });

            joinLobby.then(function (success) {
                globals.users[localConnection.uuid] = success.payload.user;
                // Preserve token for all the session!
                localConnection.sbToken = params.sbToken;
                localConnection.appKey = params.appKey;
                localConnection.valueId = params.valueId;
                localConnection.websocketUser = success.payload.user;
                localConnection.websocketUserId = success.payload.userId;
            }).catch(function (error) {
                functions.log('joinLobby: ', error, 'error');
            });

            return joinLobby;
        },
        aggregateInformation: function (localConnection, params) {
            apiUrl = defaultUrl.replace('#APP_KEY#', localConnection.appKey);

            let aggregateInformation = functions.requestApi(localConnection, '/aggregate-information', {
                'sbToken': localConnection.sbToken
            });

            aggregateInformation.then(function (success) {
                localConnection.websocket.send(toMsg(
                    {
                        event: 'aggregate-information',
                        information: success.payload.data
                    }
                ));
            }).catch(function (error) {
                functions.log('aggregateInformation: ', error, 'error');
            });

            return aggregateInformation;
        },
        updatePosition: function (localConnection, params) {
            switch (params.userType) {
                case 'driver':
                    localConnection.user.driverId = params.driverId;
                    localConnection.user.id = params.userId;
                    localConnection.user.type = 'driver';

                    try {
                        let previous = globals.drivers[localConnection.uuid].position;
                        if (previous.latitude !== params.position.latitude ||
                            previous.longitude !== params.position.longitude) {

                            globals.drivers[localConnection.uuid] = {
                                position: params.position,
                                previous: previous,
                                appKey: localConnection.appKey,
                            };
                        }
                    } catch (e) {
                        globals.drivers[localConnection.uuid] = {
                            position: params.position,
                            previous: params.position,
                            appKey: localConnection.appKey,
                        };
                    }

                    let driver = globals.drivers[localConnection.uuid];
                    functions.requestApi(localConnection, '/update-position', {
                        'position': driver.position,
                        'previous': driver.previous,
                    });

                    break;
                case 'passenger':
                    localConnection.user.clientId = params.clientId;
                    localConnection.user.id = params.userId;
                    localConnection.user.type = 'passenger';

                    try {
                        let previous = globals.passengers[localConnection.uuid].position;
                        if (previous.latitude !== params.position.latitude ||
                            previous.longitude !== params.position.longitude) {

                            globals.passengers[localConnection.uuid] = {
                                position: params.position,
                                previous: previous,
                                appKey: localConnection.appKey,
                            };
                        }
                    } catch (e) {
                        globals.passengers[localConnection.uuid] = {
                            position: params.position,
                            previous: params.position,
                            appKey: localConnection.appKey,
                        };
                    }

                    break;
            }
        },
        updateRequest: function (localConnection, params) {
            var request = params.request;

            try {
                for (let uuid in globals.passengers) {
                    var passenger = globals.allConnections[uuid];
                    if (passenger.user.clientId &&
                        parseInt(passenger.user.clientId, 10) === parseInt(request.client_id, 10)) {
                        passenger.websocket.send(toMsg(
                            {
                                event: 'update-request',
                                request: request
                            }
                        ));
                    }
                }
            } catch (error) {
                functions.log('updateRequest error: ', error, 'error');
            }
        },
        /**
         * Advertise drivers positions to nearby passengers
         * @param localConnection
         * @param params
         */
        advertDrivers : function(localConnection, params) {
            let nearbyDrivers = [];
            let passenger = globals.passengers[localConnection.uuid];
            let center = {
                latitude: passenger.position.latitude,
                longitude: passenger.position.longitude,
            };
            for (let uuid in globals.drivers) {
                let driver = globals.drivers[uuid];

                if (localConnection.appKey !== driver.appKey) {
                    continue; // Just skip drivers not inside the same app
                }

                let position = {
                    latitude: driver.position.latitude,
                    longitude: driver.position.longitude,
                };

                let inCircle = geolib.isPointInCircle(position, center, 10000);
                if (inCircle) {
                    nearbyDrivers.push(driver);
                }
            }

            // Send nearby drivers
            if (nearbyDrivers.length > 0) {
                localConnection.websocket.send(toMsg(
                    {
                        event: 'advert-drivers',
                        drivers: nearbyDrivers
                    }
                ));
            }
        },
        sendRequest: function (localConnection, params) {
            // Definitive messageId in nanoseconds
            let messageId = nanoTime();

            let sendRequest = functions.requestApi(
                localConnection,
                '/send-request',
                Object.assign(
                    {},
                    params,
                    {
                        'messageId': messageId
                    }
                )
            );

            sendRequest.then(function (response) {
                // OK
                try {
                    return response.payload;
                } catch (e) {
                    // Silent catch
                    functions.log('sendRequest error: ', e.message, 'error');
                }
            }).catch(function (error) {
                // Try to ave again, or discard if not OK!
                functions.log('sendRequest error: ', error, 'error');

                return error;
            });

            return sendRequest;
        }
    };

// Dev/Debug only!!!
functions.log('Loading config: ', config);

let init = function (httpsOptions) {
    const httpsServer = https.createServer(httpsOptions, (req, res) => {
        req.socket.write('Hello there');
        req.socket.end();
    });

    options.server = httpsServer;

    const wss = new WebSocketServer.Server(options);

    // Starting!
    wss.on('connection', function connection(websocket) {
        let tmpUuid = uuidv4();
        globals.allConnections[tmpUuid] = {
            uuid: tmpUuid,
            sbToken: null,
            appKey: null,
            valueId: null,
            websocket: websocket,
            websocketUser: null,
            websocketUserId: null,
            waitPong: false,
            waitHello: true,
            user: {
                id: null,
                type: null
            }
        };

        globals.allConnections[tmpUuid].websocket.on('message', function incoming(message) {
            // Parse payload
            let payload = JSON.parse(message);

            // Allowed events
            if (globals.allConnections[tmpUuid].websocketUserId === null &&
                payload.event !== 'join-lobby' &&
                payload.event !== 'pong' &&
                payload.event !== 'hello' &&
                payload.event !== 'ping') {
                globals.allConnections[tmpUuid].websocket.send(toMsg(
                    {
                        event: 'ack',
                        message: 'you must join the lobby first.'
                    }
                ));
                return;
            }

            switch (payload.event) {
                case 'hello':
                    globals.allConnections[tmpUuid].waitHello = false;
                    break;
                case 'ping':
                    // Server is up!
                    globals.allConnections[tmpUuid].websocket.send(toMsg(
                        {
                            event: 'pong'
                        }
                    ));
                    break;
                case 'pong':
                    // Client answered we will not disconnect the websocket!
                    globals.allConnections[tmpUuid].waitPong = false;
                    globals.allConnections[tmpUuid].websocket.send(toMsg(
                        {
                            event: 'ack',
                            message: 'pong-ok'
                        }
                    ));
                    break;
                case 'join-lobby':
                    if (globals.allConnections[tmpUuid].websocketUserId !== null) {
                        globals.allConnections[tmpUuid].websocket.send(toMsg(
                            {
                                event: 'ack',
                                message: 'already in lobby.'
                            }
                        ));
                        break;
                    }
                    functions.joinLobby(globals.allConnections[tmpUuid], payload)
                        .then(function () {
                            globals.allConnections[tmpUuid].websocket.send(toMsg(
                                {
                                    event: 'joinlobby-ok',
                                    userId: globals.allConnections[tmpUuid].websocketUserId
                                }
                            ));
                        }).catch(function (reject) {
                            globals.allConnections[tmpUuid].websocket.send(toMsg(
                                {
                                    event: 'joinlobby-ko',
                                    message: reject.message
                                }
                            ));
                        });
                    break;
                case 'update-position':
                    // No-ACK blink update!
                    functions.updatePosition(globals.allConnections[tmpUuid], payload);
                    break;
                case 'update-request':
                    functions.updateRequest(globals.allConnections[tmpUuid], payload);
                    break;
                case 'request':
                    // Send request to server
                    functions.sendRequest(globals.allConnections[tmpUuid], payload)
                        .then(function (response) {
                            globals.allConnections[tmpUuid].websocket.send(toMsg(
                                {
                                    event: 'request-ok',
                                    payload: response.payload
                                }
                            ));
                        }).catch(function (error) {
                            globals.allConnections[tmpUuid].websocket.send(toMsg(
                                {
                                    event: 'request-ko',
                                    payload: error
                                }
                            ));
                        });
                    break;
            }
        });

        globals.allConnections[tmpUuid].websocket.on('close', function () {
            functions.clearConnection(globals.allConnections[tmpUuid]);
        });

        globals.allConnections[tmpUuid].websocket.send(toMsg(
            {
                event: 'hello',
                uuid: tmpUuid,
                msg: 'Welcome user!'
            }
        ));

        // Timeout for Hello, discard after 5 seconds if no hello!
        setTimeout(function () {
            if (tmpUuid in globals.allConnections &&
                globals.allConnections[tmpUuid].waitHello === true) {
                functions.clearConnection(globals.allConnections[tmpUuid]);
            }
        }, 5000);
    });

    try {
        httpsServer.listen(config.port);
    } catch (e) {
        if (e.message.indexOf('EADDRINUSE') !== -1) {
            console.log('Server already running on port: ', config.port);
        } else {
            console.log('Error: ', e.message);
        }
        process.exit(0);
    }

    // Ping will be used to update users/drivers positions live!

    // Disabled ping in debug/test
    let pingInprogress = false;
    setInterval(function () {
        if (pingInprogress === true) {
            functions.log('Ping already in progress!');
            return;
        }
        pingInprogress = true;
        try {
            for (let uuid in globals.drivers) {
                let localConnection = globals.allConnections[uuid];
                functions.pingPong(localConnection, true)
                .then(function () {
                    // All ok
                    functions.log('ping ok', localConnection.uuid);
                })
                .catch(function () {
                    functions.log('ping ko', localConnection.uuid);
                    functions.clearConnection(localConnection);
                });
            }

            for (let uuid in globals.drivers) {
                let localConnection = globals.allConnections[uuid];
                functions.aggregateInformation(localConnection, {});
            }

            // Advert drivers to all passengers (for now), we will filter based on position in a future step
            for (let uuid in globals.passengers) {
                let localConnection = globals.allConnections[uuid];
                functions.advertDrivers(localConnection, {});
                functions.aggregateInformation(localConnection, {});
            }
        } catch (e) {
            functions.log(e, e.message);
        }

        pingInprogress = false;
    }, 2000);

};

// Init when request is OK!
axios.get(defaultUrl.replace('#APP_KEY#/', '') + '/settings', {
    responseType: 'json',
    httpsAgent: httpsAgent,
    headers: requestDefaultHeaders
}).then(function (response) {
    let httpsOptions = {
        key: response.data.privateKey,
        ca: response.data.chain,
        cert: response.data.certificate
    };

    try {
        init(httpsOptions);
    } catch (e) {
        if (e.message.indexOf('EADDRINUSE') !== -1) {
            console.log('Server already running on port: ', config.port);
            process.exit(0);
        } else {
            console.log(e, e.message);
        }
    }
}).catch(function (error) {
    functions.log('Something went wrong: ', error, 'error');
});

// Listen out unhandeld promises
process.on('unhandledRejection', (reason, p) => {
    console.log('Unhandled Rejection at: Promise', p, 'reason:', reason);
});
