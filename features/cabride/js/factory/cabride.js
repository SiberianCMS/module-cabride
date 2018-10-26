/**
 * Cabride factory
 */
angular.module('starter')
    .factory('Cabride', function () {
        let factory = {};

        factory.setValueId = function (valueId) {
            factory.value_id = valueId;
        };

        return factory;
    });
