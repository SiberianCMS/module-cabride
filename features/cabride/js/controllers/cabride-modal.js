/**
 * Cabride version 2 controllers
 */
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
        Loader.show();

        CabridePayment
        .saveCard()
        .then(function (payload) {
            $scope.vaults = payload.vaults;
            $scope.addEditCard = false;
        }, function (errorMessage) {
            Dialog.alert("Error", errorMessage, "OK", 5000);
        }).then(function () {
            Loader.hide();
        });
    };
});

angular.module('starter')
.controller('RequestDetailsController', function ($scope, $translate, Cabride, CabrideUtils) {
    angular.extend($scope, {
        isLoading: false
    });

    $scope.calendar = function (timestampSeconds) {
        return moment(timestampSeconds * 1000).calendar();
    };

    $scope.distance = function (request) {
        var unit = Cabride.settings.distance_unit;
        var distance = request.distance / 1000;
        switch (unit) {
            case "mi":
                    return Math.ceil(distance) + " mi";
                break;
            case "km": default:
                return Math.ceil(distance) + " Km";
            break;
        }
    };

    $scope.duration = function (request) {
        return CabrideUtils.toHHMM(request.duration);
    };

    $scope.source = function (source) {
        if ($scope.userType === "driver" && source === "driver") {
            return $translate.instant("You");
        } else if ($scope.userType === "client" && source === "client") {
            return $translate.instant("You");
        }

        switch (source) {
            case "cron":
                return $translate.instant("System");
            case "admin":
                return $translate.instant("App manager");
            case "driver":
                return $translate.instant("Driver");
            case "client":
                return $translate.instant("Client");
        }

        // Return unchanged if no match
        return source;
    };

    $scope.status = function (status) {
        // "pending", "accepted", "onway", "inprogress", "declined", "done", "aborted", "expired"
        switch (status) {
            case "pending":
                return $translate.instant("Created");
            case "accepted":
                return $translate.instant("Accepted");
            case "onway":
                return $translate.instant("Driver on way");
            case "inprogress":
                return $translate.instant("Course in progress");
            case "declined":
                return $translate.instant("Declined");
            case "done":
                return $translate.instant("Course done");
            case "aborted":
                return $translate.instant("Aborted");
            case "expired":
                return $translate.instant("Expired");
        }
    };

    $scope.imageCarPath = function (image) {
        if (image === "") {
            return IMAGE_URL + "app/local/modules/Cabride/resources/design/desktop/flat/images/car-icon.png";
        }
        return IMAGE_URL + "images/application" + image;
    };

    $scope.imageRoutePath = function (image) {
        if (image === "") {
            return IMAGE_URL + "app/local/modules/Cabride/resources/design/desktop/flat/images/no-route.jpg";
        }
        return IMAGE_URL + "images/application" + image;
    };

    $scope.customerPhoto = function (image) {
        if (image === "" || image === null) {
            return "./features/cabride/assets/templates/images/015-no-photo.png";
        }
        return IMAGE_URL + "images/customer" + image;
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
});
