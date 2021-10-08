/**
 * Cabride backoffice settings
 */
App.config(function ($routeProvider) {
    $routeProvider
		.when(BASE_URL + '/cabride/backoffice_view', {
			controller: 'CabrideViewController',
			templateUrl: BASE_URL + '/cabride/backoffice_view/template'
		});
}).controller('CabrideViewController', function ($scope, $window, Header, Cabride, $timeout) {
    angular.extend($scope, {
        header: new Header(),
        content_loader_is_visible: true,
		settings: {},
		logContent: [],
        logError: false,
        logErrorMessage: ""
    });

    $scope.header.loader_is_visible = false;

    Cabride
        .loadData()
		.success(function (payload) {
			$scope.header.title = payload.title;
			$scope.header.icon = payload.icon;
			$scope.settings = payload.settings;
		}).finally(function () {
			$scope.content_loader_is_visible = false;
		});

    $scope.save = function () {
        $scope.content_loader_is_visible = true;
        Cabride
			.save($scope.settings)
            .success(function (payload) {
                $scope.message
                .setText(payload.message)
                .isError(false)
                .show();
            }).error(function (payload){
                $scope.message
                .setText(payload.message)
                .isError(true)
                .show();
            }).finally(function () {
                $scope.content_loader_is_visible = false;
			});
	};

    $scope.restartSocket = function () {
        $scope.content_loader_is_visible = true;
        Cabride
        .restartSocket()
        .success(function (payload) {
            $scope.message
            .setText(payload.message)
            .isError(false)
            .show();

            $scope.offset = 0;
        }).error(function (payload){
            $scope.message
            .setText(payload.message)
            .isError(true)
            .show();
        }).finally(function () {
            $scope.content_loader_is_visible = false;
        });
    };

    $scope.nextTimeout = 3000;
    $scope.nextLog = function () {
        Cabride
            .liveLog($scope.offset)
            .success(function (payload) {
                $scope.logError = false;
                $scope.logErrorMessage = "";
                $scope.offset = payload.offset;

                /// concat then keep only 1000 lines
                $scope.nextTimeout = 3000;
                if (payload.txtContent.length > 0) {
                    $scope.logContent = $scope.logContent.concat(payload.txtContent);
                    if ($scope.logContent.length > 1000) {
                        var endRemove = $scope.logContent.length - 1000;
                        $scope.logContent.splice(0, endRemove);
                    }
                    $scope.txtContent = $scope.logContent.join("\n");
                } else {
                    $scope.nextTimeout = 5000;
                }

                // Load next only on success!
                $timeout(function () {
                    $scope.nextLog();
                }, $scope.nextTimeout);
            }).error(function (payload){
                $scope.offset = 0;
                $scope.logError = true;
                $scope.logErrorMessage = payload.message;
            });
    };

    $timeout(function () {
        $scope.nextLog();
    }, 1500);
});
