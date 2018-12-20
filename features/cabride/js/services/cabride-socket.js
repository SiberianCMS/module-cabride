/**
 * CabrideSocket service websocket
 */
angular.module('starter')
    .service('CabrideSocket', function ($log, $rootScope) {
        var service = {
            hello: {
                event: 'hello'
            },
            websocket: null
        };

        /**
         * Wrapper for JSON.stringify
         * @param object
         */
        service.toMsg = function (object) {
            return JSON.stringify(object);
        };

        /**
         * Simple alias to send raw objects
         * @param object
         */
        service.sendMsg = function (object) {
            service.socket.send(service.toMsg(object));
        };

        /**
         * Connection handler/init
         * @param socketUri
         * @param onMessageCallback
         * @param onErrorCallback
         */
        service.connect = function (socketUri, onMessageCallback, onErrorCallback) {
            if (typeof onMessageCallback !== 'function') {
                $log.error('onMessageCallback is required and must be a function!');
                return;
            }

            service.socket = new WebSocket(socketUri);

            service.socket.onopen = function (event) {
                // Sends Hello to identify as active and protocol is working!
                service.socket.sendMsg(service.hello);
                $rootScope.$broadcast('cabride.isAlive');
            };

            service.socket.onerror = function (error) {
                $log.error('CabrideSocket Error ' + error);

                // Transfer the error to callback only if defined!
                if (typeof onErrorCallback !== 'function') {
                    onErrorCallback(error);
                }
            };

            service.socket.onmessage = function (event) {
                // Transfer data to the callback handler!
                onMessageCallback(JSON.parse(event.data));
            };
        };

        return service;
    });
