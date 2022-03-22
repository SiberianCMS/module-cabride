angular
    .module('starter')
    .controller('CabrideVehicleType', function ($scope, $translate, Cabride, Dialog, CabrideBase) {
        angular.extend($scope, CabrideBase, {
            isLoading: false,
            enableCustomForm: Cabride.settings.enableCustomForm,
            currentVehicleId: null,
            currentVehicleType: null
        });

        $scope.select = function (vehicleId, vehicleType) {
            $scope.currentVehicleId = vehicleId;
            $scope.currentVehicleType = vehicleType;

            // Directly go to the next page!
            if (!$scope.enableCustomForm) {
                $scope.selectVehicle($scope.currentVehicleType);
            }
        };

        $scope.canMakeOffer = function () {
            return Cabride.settings.canMakeOffer;
        };

        $scope.getCurrency = function () {
            return Cabride.settings.currency;
        };

        $scope.resetFields = function () {
            Cabride.settings.customFormFieldsUser = angular.copy(Cabride.settings.customFormFields);
        };

        $scope.customFormFields = function () {
            return Cabride.settings.customFormFieldsUser;
        };

        $scope.imagePath = function (image) {
            if (image === "") {
                return IMAGE_URL + "app/local/modules/Cabride/resources/design/desktop/flat/images/car-icon.png";
            }
            return IMAGE_URL + "images/application" + image;
        };

        $scope.validate = function () {
            if ($scope.customFormIsValid()) {
                $scope.selectVehicle($scope.currentVehicleType);
            }
        };

        $scope.makeOffer = function () {
            if ($scope.customFormIsValid()) {

                Dialog
                    .prompt(
                        'Make an offer',
                        'Please type down the amount you want to offer for this ride!',
                        'number',
                        '',
                        ['CONFIRM', 'CANCEL'],
                        -1,
                        'cabride')
                    .then(function (amount) {

                        var currency = $scope.getCurrency();
                        var current = parseFloat(amount);
                        if (!Number.isFinite(current) ||
                            current <= 0) {
                            Dialog.alert('Error', 'The offered amount is not valid!', 'OK', -1, 'cabride');
                            return;
                        }

                        var formattedAmount = amount.toFixed(currency.decimal_digits) + ' ' + currency.symbol;
                        var textParts = [
                            $translate.instant('You are about to offer', 'cabride'),
                            formattedAmount,
                            $translate.instant('Please confirm!', 'cabride'),
                        ];

                        Dialog
                            .confirm('Your offer', textParts.join('<br />'), ['YES', 'NO'], '', 'cabride')
                            .then(function (confirm) {
                                if (confirm) {
                                    $scope.selectVehicle($scope.currentVehicleType);
                                }
                            });
                    })
            }
        };

        $scope.checkValue = function (field) {
            field.value += '';
            field.value = field.value.replace(/[^0-9\.,\-]/, '');
        };

        $scope.customFormIsValid = function () {

            var required = ['number', 'password', 'text', 'textarea', 'date', 'datetime', 'clickwrap', 'select'];
            var isValid = true;
            var invalidFields = [];
            $scope.customFormFields().forEach(function (field) {
                if (required.indexOf(field.type) >= 0 && field.is_required) {
                    if (field.type === 'number') {
                        var current = parseFloat(field.value);
                        if (!Number.isFinite(current)) {
                            text = $translate.instant('is not a number', 'cabride');
                            invalidFields.push('&nbsp;-&nbsp;' + field.label + ' ' + text);
                            isValid = false;
                        }
                        var min = Number.parseInt(field.min);
                        var max = Number.parseInt(field.max);
                        var step = parseFloat(field.step);
                        var text;
                        if (current < min || current > max) {
                            text = $translate.instant('is not inside range', 'cabride') + ' ' + min + '-' + max;
                            invalidFields.push('&nbsp;-&nbsp;' + field.label + ' ' + text);
                            isValid = false;
                        }
                        if (step !== 0 && !Number.isInteger(current / step)) {
                            text = $translate.instant('must match increment', 'cabride') + ' ' + step;
                            invalidFields.push('&nbsp;-&nbsp;' + field.label + ' ' + text);
                            isValid = false;
                        }
                    } else if (field.value === undefined ||
                        (field.value + '').trim().length === 0) {
                        invalidFields.push('&nbsp;-&nbsp;' + field.label);
                        isValid = false;
                    }
                }
            });

            // Check vehicle type
            if ($scope.currentVehicleId === null) {
                var vehicleType = $translate.instant('Vehicle type', 'cabride');
                invalidFields.push('&nbsp;-&nbsp;' + vehicleType);
                isValid = false;
            }

            if (!isValid) {
                Dialog.alert('Required fields', invalidFields.join('<br />'), 'OK', -1, 'form2');
            }

            return isValid;
        };

        $scope.resetFields();
    });
