/**
 * CabrideCancelledRides
 */
angular
    .module('starter')
    .controller('CabrideCancelledRides', function ($scope, $translate, Cabride, Dialog, CabrideBase) {
    angular.extend($scope, CabrideBase, {
        isLoading: false,
        pageTitle: $translate.instant('Cancelled requests', 'cabride'),
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
            Dialog.alert('Error', error.message, 'OK', -1, 'cabride');
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
