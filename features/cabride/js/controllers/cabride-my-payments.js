angular.module('starter')
.controller('CabrideMyPayments', function ($rootScope, $scope, $filter, $translate, $ionicScrollDelegate,
                                           Cabride, CabrideUtils, Dialog, CabrideBase) {
    angular.extend($scope, CabrideBase, {
        isLoading: false,
        pageTitle: $translate.instant('My payments', 'cabride'),
        valueId: Cabride.getValueId(),
        payments: [],
    });

    $scope.loadPage = function () {
        $scope.isLoading = true;
        Cabride
        .getMyPayments()
        .then(function (payload) {
            $scope.payments = payload.payments;
        }, function (error) {
            Dialog.alert("Error", error.message, "OK", -1, "cabride");
        }).then(function () {
            $scope.isLoading = false;
        });
    };

    $scope.refresh = function () {
        $scope.loadPage();
    };

    $scope.$on('cabride.updateRequest', function (event, request) {
        $scope.refresh();
    });

    $scope.loadPage();
});
