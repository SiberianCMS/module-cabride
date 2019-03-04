/**
 * Cabride version 2 controllers
 */
angular.module('starter')
.controller('CabridePaymentType', function ($scope, Dialog, CabridePayment) {
    angular.extend($scope, {
        hasPaymentType: false,
        addEditCard: false
    });

    $scope.select = function (paymentType) {
        switch (paymentType) {
            case "credit-card":
                $scope.addEditCard = true;
                CabridePayment.addEditCard();
                break;
            case "cash":
                // Validate directly!
                Dialog
                .alert("Thanks", "You'll pay the driver directly when the course ends!", "OK", 3500)
                .then(function () {
                    $scope.validateRequest("cash");
                });
                break;
        }
    };

    $scope.selectVault = function (vault) {
        $scope.validateRequest(vault);
    };

    $scope.creditCardBrand = function (brand) {
        switch (brand.toLowerCase()) {
            case "visa":
                return "./features/cabride/assets/templates/images/011-cc-visa.svg";
            case "mastercard":
                return "./features/cabride/assets/templates/images/012-cc-mastercard.svg";
            case "american express":
                return "./features/cabride/assets/templates/images/013-cc-amex.png";
        }
        return "./features/cabride/assets/templates/images/014-cc.svg";
    };

    $scope.saveCard = function () {
        CabridePayment
        .saveCard()
        .then(function (payload) {
            $scope.vaults = payload.vaults;
            $scope.addEditCard = false;
        }, function (errorMessage) {
            Dialog.alert("Error", errorMessage, "OK", 5000);
        });
    };
});
