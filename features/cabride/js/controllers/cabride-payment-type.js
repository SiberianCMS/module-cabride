angular.module('starter')
.controller('CabridePaymentType', function ($scope, $timeout, Cabride, Dialog, PaymentStripe, Loader) {
    angular.extend($scope, {
        hasPaymentType: false,
        addEditCard: false,
        paymentProvider: false,
        cardActions: []
    });

    $scope.select = function (paymentType) {
        switch (paymentType) {
            case "stripe":
            case "credit-card":
                Loader.show();

                PaymentStripe
                .setPublishableKey(Cabride.settings.stripePublicKey)
                .then(function () {
                    PaymentStripe
                    .cardForm($scope.saveCardSuccess, $scope.saveCardError)
                    .then(function () {
                        $scope.paymentProvider = Cabride.settings.paymentProvider;
                        $scope.addEditCard = true;
                        Loader.hide();
                    });
                }, function (error) {
                    Dialog.alert("Error", error, "OK", -1, "payment_stripe");
                });

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

    $scope.getSetupIntentEndpoint = function () {
        return "/cabride/mobile_payment/get-setup-intent/value_id/" + Cabride.value_id;
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

    $scope.saveCardSuccess = function (tokenResult) {
        Loader.show();

        Cabride
        .saveCard(tokenResult, "stripe")
        .then(function (payload) {
            PaymentStripe.clearForm();

            $timeout(function () {
                $scope.vaults = angular.copy(payload.vaults);
            });

            $scope.addEditCard = false;
        }, function (errorMessage) {
            Dialog.alert("Error", errorMessage, "OK", 5000, "cabride");
        }).then(function () {
            Loader.hide();
        });
    };

    $scope.saveCardError = function (error) {
        Dialog.alert("Error", error, "OK", 5000, "cabride");
    };

    $scope.cardActions = [
        {
            callback: $scope.selectVault,
            icon: "icon ion-android-arrow-forward"
        }
    ];
});