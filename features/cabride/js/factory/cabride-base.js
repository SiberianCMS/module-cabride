/**
 * CabrideUtils factory
 */
angular.module('starter')
    .factory('CabrideBase', function ($injector, $rootScope, $window, $translate, LinkService) {
        var factory = {};

        // We must inject it to prevent loop cycles
        var Cabride = $injector.get('Cabride');
        var CabrideUtils = $injector.get('CabrideUtils');

        factory.seatsEnabled = function () {
            return Cabride.settings.enableSeats;
        };

        factory.tourEnabled = function () {
            return Cabride.settings.enableTour;
        };

        factory.duration = function (request) {
            return CabrideUtils.toHmm(request.duration);
        };

        factory.durationTour = function (request) {
            return CabrideUtils.toHmm(request.duration);
        };

        factory.distance = function (request) {
            return CabrideUtils.distance(request);
        };

        factory.openMenu = function () {
            CabrideUtils.openMenu();
        };

        factory.cs = function () {
            return Cabride.currencySymbol();
        };

        factory.getPageTitle = function () {
            return Cabride.settings.pageTitle;
        };

        factory.passengerPicture = function () {
            return Cabride.settings.passengerPicture;
        };

        factory.driverPicture = function () {
            return Cabride.settings.driverPicture;
        };

        factory.reconnect = function () {
            Cabride.init();
        };

        factory.isTaxiLayout = function () {
            return Cabride.isTaxiLayout;
        };

        factory.expiration = function (request) {
            return moment().add(parseInt(request.expires_in, 10), 'seconds').fromNow();
        };

        factory.details = function (request, pov) {
            Cabride.requestDetailsModal($rootScope.$new(true), request.request_id, pov);
        };

        factory.callClient = function (request) {
            LinkService.openLink('tel:' + request.client_phone);
        };

        factory.getRatingIcon = function(request, value) {
            return (request.course_rating >= value) ? 'ion-android-star' : 'ion-android-star-outline';
        };

        factory.rateCourse = function (request) {
            Cabride.rateCourseModal(request);
        };

        factory.eta = function (request) {
            // Ensure values are integers
            var duration = parseInt(request.eta_driver, 10) * 1000;
            return moment(duration).fromNow();
        };

        factory.canCancel = function (request) {
            return ['pending', 'accepted'].indexOf(request.status) !== -1;
        };

        factory.callDriver = function (request) {
            LinkService.openLink('tel:' + request.driver_phone);
        };

        factory.cancel = function (request) {
            Cabride.cancelModal(request, 'client');
        };

        factory.dateFormat = function (timestampSeconds) {
            return moment(timestampSeconds * 1000).calendar();
        };

        factory.textSeats = function (count) {
            return (count > 1) ? $translate.instant('seats', 'cabride') :
                $translate.instant('seat', 'cabride');
        };

        factory.imagePath = function (image) {
            if (image === '') {
                return IMAGE_URL + 'app/local/modules/Cabride/resources/design/desktop/flat/images/no-route.jpg';
            }
            return IMAGE_URL + 'images/application' + image;
        };

        factory.distanceUnit = function () {
            return Cabride.settings.distanceUnit;
        };

        factory.pricingDriver = function () {
            return Cabride.settings.pricingMode === 'driver';
        };


        return factory;
    });
