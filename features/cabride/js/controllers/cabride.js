/**
 * Cabride version 2 controllers
 */
angular.module('starter')
    .controller('CabrideHome', function ($scope, Cabride) {
        // @todo
    });

/**
 * App load initialization
 */
angular.module('starter').run(function (Application) {
    Application.loaded.then(function () {
        console.log('cabride loaded');
        // App runtime!
    });
});
