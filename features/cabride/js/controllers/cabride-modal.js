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
                    .alert("Thanks", "You'll pay the driver directly when the course ends!", "OK", 2350)
                    .then(function () {
                        $scope.validateRequest();
                    });
                break;
        }
    };
});
