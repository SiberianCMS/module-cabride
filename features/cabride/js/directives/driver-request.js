/**
 * driverRequest
 *
 * @author Xtraball SAS <dev@xtraball.com>
 * @version 3.0.1
 */
angular
    .module('starter')
    .directive('driverRequest', function () {
        return {
            restrict: 'E',
            templateUrl: 'features/cabride/assets/templates/l1/directives/driver-request.html'
        };
    });
