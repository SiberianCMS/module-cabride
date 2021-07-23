/**
 * clientRequest
 *
 * @author Xtraball SAS <dev@xtraball.com>
 * @version 3.0.1
 */
angular
    .module('starter')
    .directive('clientRequest', function (CabrideBase) {
        return {
            restrict: 'E',
            scope: {
                request: '='
            },
            templateUrl: 'features/cabride/assets/templates/l1/directives/client-request.html',
            link: function (scope) {},
            controller: function ($scope) {
                angular.extend($scope, CabrideBase);
            }
        };
    });


