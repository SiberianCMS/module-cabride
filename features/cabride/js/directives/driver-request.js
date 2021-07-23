/**
 * driverRequest
 *
 * @author Xtraball SAS <dev@xtraball.com>
 * @version 3.0.1
 */
angular
    .module('starter')
    .directive('driverRequest', function (CabrideBase) {
        return {
            restrict: 'E',
            scope: {
                request: '='
            },
            templateUrl: 'features/cabride/assets/templates/l1/directives/driver-request.html',
            link: function (scope) {},
            controller: function ($scope) {
                angular.extend($scope, CabrideBase);
            }
        };
    });


