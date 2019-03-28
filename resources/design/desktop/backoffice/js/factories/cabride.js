/**
 *
 */
App.factory("Cabride", function ($http, Url) {
    let factory = {};

    /**
     *
     * @returns {*}
     */
    factory.loadData = function () {
        return $http({
            method: "GET",
            url: Url.get("cabride/backoffice_view/load"),
            cache: false
        });
    };

    /**
     *
     * @param settings
     * @returns {*}
     */
    factory.save = function (settings) {
        return $http({
            method: "POST",
            url: Url.get("cabride/backoffice_view/save"),
            data: settings,
            cache: false
        });
    };

    /**
     *
     * @returns {*}
     */
    factory.restartSocket = function () {
        return $http({
            method: "POST",
            url: Url.get("cabride/backoffice_view/restart-socket"),
            data: settings,
            cache: false
        });
    };
	
    return factory;
});
