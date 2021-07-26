/**
 * clientRequest
 *
 * @author Xtraball SAS <dev@xtraball.com>
 * @version 3.0.1
 */
angular
    .module('starter')
    .directive('clientRequest', function () {
        return {
            restrict: 'E',
            templateUrl: 'features/cabride/assets/templates/l1/directives/client-request.html'
        };
    });
