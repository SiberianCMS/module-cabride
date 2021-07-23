angular.module('starter')
.controller('CabrideMyRides', function ($scope, $filter, $translate, $ionicScrollDelegate,
                                        Cabride, CabrideUtils, Dialog, $window, CabrideBase) {
    angular.extend($scope, CabrideBase, {
        isLoading: false,
        pageTitle: $translate.instant("My rides", "cabride"),
        valueId: Cabride.getValueId(),
        filtered: null,
        toRate: null,
        filterName: "inprogress",
        collection: []
    });

    $scope.loadPage = function () {
        $scope.isLoading = true;
        Cabride
        .getMyRides()
        .then(function (payload) {
            $scope.collection = payload.collection;
            $scope.filtered = $filter("cabrideStatusFilter")($scope.collection, $scope.filterName);
            $scope.toRate = $filter("cabrideStatusFilter")($scope.collection, "torate");
        }, function (error) {
            Dialog.alert("Error", error.message, "OK", -1, "cabride");
        }).then(function () {
            $scope.isLoading = false;
        });
    };

    $scope.refresh = function () {
        $scope.loadPage();
    };

    $scope.statusFilter = function (filter) {
        // "pending", "accepted", "declined", "done", "aborted", "expired"
        if (filter === "inprogress") {
            $scope.filterName = "inprogress";
        } else if (filter === "torate") {
            $scope.filterName = "torate";
        } else if (filter === "archived") {
            $scope.filterName = "archived";
        }

        $ionicScrollDelegate.scrollTop();
    };

    $scope.$watch("filterName", function () {
        $scope.filtered = $filter("cabrideStatusFilter")($scope.collection, $scope.filterName);
        $scope.toRate = $filter("cabrideStatusFilter")($scope.collection, "torate");
    });

    $scope.$on('cabride.updateRequest', function (event, request) {
        $scope.refresh();
    });

    $scope.loadPage();
});
