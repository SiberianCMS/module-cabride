angular.module('starter')
.controller('CabridePaymentHistory', function ($scope, $translate, $ionicScrollDelegate, Cabride,
                                               CabrideUtils, Dialog, CabrideBase) {

    angular.extend($scope, CabrideBase, {
        isLoading: false,
        pageTitle: $translate.instant("Payment history", "cabride"),
        valueId: Cabride.getValueId(),
        filtered: null,
        filterName: "card",
        keyName: "cardPayments",
        pendingPayouts: [],
        cashReturns: [],
        collections: [],
    });

    $scope.loadPage = function () {
        $scope.isLoading = true;
        Cabride
        .getPaymentHistory()
        .then(function (payload) {
            $scope.wording = payload.wording;
            $scope.collections = payload.collections;
            $scope.cashReturns = payload.cashReturns;
            $scope.pendingPayouts = payload.pendingPayouts;
            $scope.filtered = $scope.collections[$scope.keyName];
        }, function (error) {
            Dialog.alert("Error", error.message, "OK", -1, "cabride");
        }).then(function () {
            $scope.isLoading = false;
        });
    };

    $scope.refresh = function () {
        $scope.loadPage();
    };

    $scope.creditCardBrand = function (brand) {
        var _localBrand = brand === null ? '' : brand;
        switch (_localBrand.toLowerCase()) {
            case "visa":
                return "./features/cabride/assets/templates/images/011-cc-visa.svg";
            case "mastercard":
                return "./features/cabride/assets/templates/images/012-cc-mastercard.svg";
            case "american express":
                return "./features/cabride/assets/templates/images/013-cc-amex.png";
        }
        return "./features/cabride/assets/templates/images/014-cc.svg";
    };

    $scope.statusFilter = function (filter) {
        switch (filter) {
            case "card":
                $scope.filterName = "card";
                $scope.keyName = "cardPayments";
                break;
            case "cash":
                $scope.filterName = "cash";
                $scope.keyName = "cashPayments";
                break;
        }
        $scope.filtered = $scope.collections[$scope.keyName];
        $ionicScrollDelegate.scrollTop();
    };

    $scope.loadPage();
});
