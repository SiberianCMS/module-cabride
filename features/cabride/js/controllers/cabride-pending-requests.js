angular.module('starter')
.controller('CabridePendingRequests', function ($scope, $translate, $state, Cabride, CabrideUtils, Dialog, Loader, CabrideBase) {
    angular.extend($scope, CabrideBase, {
        isLoading: false,
        pageTitle: $translate.instant("Pending requests", "cabride"),
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
            Dialog.alert("Error", error.message, "OK", -1, "cabride");
        }).then(function () {
            $scope.isLoading = false;
        });
    };

    $scope.refresh = function () {
        $scope.loadPage();
    };

    $scope.decline = function (request) {
        Loader.show();
        Cabride
        .declineRide(request.request_id)
        .then(function (payload) {
            Cabride.updateRequest(request);
            Dialog
            .alert("", payload.message, "OK", 2350)
            .then(function () {
                Loader.hide();
                $state.go("cabride-declined-rides");
            });
        }, function (error) {
            Dialog.alert("Error", error.message, "OK", -1, "cabride");
        }).then(function () {
            Loader.hide();
            $scope.refresh();
        });
    };

    $scope.accept = function (request) {
        Loader.show();
        if (request.type === 'course') {
            $scope.acceptCourse(request);
        } else if (request.type === 'tour') {
            $scope.acceptTour(request);
        }
    };
    
    $scope.acceptCourse = function (request) {
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
                        Cabride.updateRequest(request);
                        Dialog
                            .alert("", payload.message, "OK", 2350)
                            .then(function () {
                                Loader.hide();
                                $state.go("cabride-accepted-requests");
                            });
                    }, function (error) {
                        Dialog.alert("Error", error.message, "OK", -1, "cabride");
                    }).then(function () {
                        Loader.hide();
                        $scope.refresh();
                    });
            }, function (error) {
                Dialog.alert("Error", error[1], "OK", -1, "cabride");
                Loader.hide();
                $scope.refresh();
            });
    };

    $scope.acceptTour = function (request) {
        Cabride
            .acceptRide(request.request_id, {})
            .then(function (payload) {
                Cabride.updateRequest(request);
                Dialog
                    .alert("", payload.message, "OK", 2350)
                    .then(function () {
                        Loader.hide();
                        $state.go("cabride-accepted-requests");
                    });
            }, function (error) {
                Dialog.alert("Error", error.message, "OK", -1, "cabride");
            }).then(function () {
                Loader.hide();
                $scope.refresh();
            });
    };

    $scope.$on('cabride.updateRequest', function (event, request) {
        $scope.refresh();
    });

    $scope.loadPage();
});
