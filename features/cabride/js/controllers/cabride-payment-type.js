angular.module('starter')
.controller('CabridePaymentType', function ($scope, $timeout, Cabride, Dialog) {
    angular.extend($scope, {
        hasPaymentType: false,
        addEditCard: false,
        paymentProvider: false,
        cardActions: [],
        //
        stripeOptions: {},
        stripeCardsOptions: {},
        stripeFormOptions: {}
    });

    $scope.selectCard = function () {
        // Validate directly!
        Dialog
        .alert(
            "Thanks",
            "You'll pay the driver directly when the course ends!",
            "OK",
            3500)
        .then(function () {
            $scope.validateRequest("cash");
        });
    };

    $scope.selectCard = function (card) {
        $scope.validateRequest(card);
    };

    $scope.stripeCardsOptions = {
        actions: [
            {
                callback: $scope.selectCard,
                icon: "icon ion-android-arrow-forward"
            }
        ]
    };
});
