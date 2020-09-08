angular.module('starter')
.controller('CabridePaymentType', function ($scope, Dialog, CabridePayment, Loader) {
    angular.extend($scope, {
        hasPaymentType: false,
        addEditCard: false,
        paymentProvider: false
    });

    $scope.select = function (paymentType) {
        switch (paymentType) {
            case "credit-card":
                Loader.show();

                CabridePayment
                .addEditCard()
                .then(function () {
                    $scope.addEditCard = true;
                    $scope.paymentProvider = CabridePayment.settings.paymentProvider;
                }).then(function () {
                    Loader.hide();
                });

                break;
            case "cash":
                // Validate directly!
                Dialog
                .alert("Thanks", "You'll pay the driver directly when the course ends!", "OK", 3500, 'cabride')
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
        Loader.show();

        CabridePayment
        .saveCard()
        .then(function (payload) {
            $scope.vaults = payload.vaults;
            $scope.addEditCard = false;
        }, function (errorMessage) {
            Dialog.alert("Error", errorMessage, "OK", 5000, "cabride");
        }).then(function () {
            Loader.hide();
        });
    };
});
