/**
 * CabrideAcceptedRequests
 *
 * @version 3.0.1
 */
angular
    .module('starter')
    .controller('CabrideAcceptedRequests', function ($scope, $translate, $state, Cabride, CabrideUtils, Dialog, Loader,
                                                     $window, CabrideBase) {
        angular.extend($scope, CabrideBase, {
            isLoading: false,
            pageTitle: $translate.instant('Accepted requests', 'cabride'),
            valueId: Cabride.getValueId(),
            showPassengerPhone: Cabride.settings.showPassengerPhone,
            collection: []
        });

        $scope.loadPage = function () {
            $scope.isLoading = true;
            Cabride
                .getAcceptedRides()
                .then(function (payload) {
                    $scope.collection = payload.collection;
                }, function (error) {
                    Dialog.alert("Error", error.message, 'OK', -1, 'cabride');
                }).then(function () {
                $scope.isLoading = false;
            });
        };

        $scope.refresh = function () {
            $scope.loadPage();
        };

        $scope.driveToPassenger = function (request) {
            if (request.type === 'course') {
                $scope.driveToPassengerCourse(request);
            } else if (request.type === 'tour') {
                $scope.driveToPassengerTour(request);
            }
        };

        $scope.driveToPassengerTour = function (request) {
            Loader.show();
            Cabride
                .driveToPassenger(request.request_id, {})
                .then(function (payload) {
                    Cabride.updateRequest(request);
                    Dialog
                        .alert('', payload.message, 'OK', 2350)
                        .then(function () {
                            Loader.hide();
                            Navigator.navigate(payload.driveTo);
                        });
                }, function (error) {
                    Dialog.alert('Error', error.message, 'OK', -1, 'cabride');
                }).then(function () {
                Loader.hide();
                $scope.refresh();
            });
        };

        $scope.driveToPassengerCourse = function (request) {
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
                    .driveToPassenger(request.request_id, route)
                    .then(function (payload) {
                        Cabride.updateRequest(request);
                        Dialog
                            .alert('', payload.message, 'OK', 2350)
                            .then(function () {
                                Loader.hide();
                                Navigator.navigate(payload.driveTo);
                            });
                    }, function (error) {
                        Dialog.alert('Error', error.message, 'OK', -1, 'cabride');
                    }).then(function () {
                    Loader.hide();
                    $scope.refresh();
                });
            }, function (error) {
                Dialog.alert('Error', error[1], 'OK', -1, 'cabride');
                Loader.hide();
                $scope.refresh();
            });
        };

        $scope.driveToDestination = function (request) {
            Loader.show();
            Cabride
                .driveToDestination(request.request_id)
                .then(function (payload) {
                    Cabride.updateRequest(request);
                    Dialog
                        .alert('', payload.message, 'OK', 2350)
                        .then(function () {
                            Loader.hide();
                            Navigator.navigate(payload.driveTo);
                        });
                }, function (error) {
                    Dialog.alert('Error', error.message, 'OK', -1, 'cabride');
                }).then(function () {
                Loader.hide();
                $scope.refresh();
            });
        };

        $scope.complete = function (request) {
            Loader.show();
            Cabride
                .completeRide(request.request_id)
                .then(function (payload) {
                    Cabride.updateRequest(request);
                    Dialog
                        .alert('', payload.message, 'OK', 2350)
                        .then(function () {
                            Loader.hide();
                            $state.go('cabride-completed-rides');
                        });
                }, function (error) {
                    Dialog.alert('Error', error.message, 'OK', -1, 'cabride');
                }).then(function () {
                Loader.hide();
                $scope.refresh();
            });
        };

        $scope.notifyClient = function (request) {
            Loader.show();
            Cabride
                .notifyClient(request.request_id)
                .then(function (payload) {
                    Dialog
                        .alert('', payload.message, 'OK', 2350)
                        .then(function () {
                            Loader.hide();
                        });
                }, function (error) {
                    Dialog.alert('Error', error.message, 'OK', -1, 'cabride');
                }).then(function () {
                Loader.hide();
            });
        };

        $scope.$on('cabride.updateRequest', function (event, request) {
            $scope.refresh();
        });

        $scope.loadPage();
    });
