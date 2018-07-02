#!/usr/bin/env php

<?php

//APP_PATH: yaf root dir
//APP_CONFIG: yaf framework config file
//APP_MODE: env mode type ,such as production/develop

use Yaf\Application;

define('APP_PATH', __DIR__ . '/..');
define('APP_CONFIG', APP_PATH . '/conf/application.ini');
define('APP_MODE', 'develop');

defined('FRAMEWORK_ERR_NOTFOUND_MODULE') or define('FRAMEWORK_ERR_NOTFOUND_MODULE', 515);
defined('FRAMEWORK_ERR_NOTFOUND_CONTROLLER') or define('FRAMEWORK_ERR_NOTFOUND_CONTROLLER', 516);
defined('FRAMEWORK_ERR_NOTFOUND_ACTION') or define('FRAMEWORK_ERR_NOTFOUND_ACTION', 517);
defined('FRAMEWORK_ERR_NOTFOUND_VIEW') or define('FRAMEWORK_ERR_NOTFOUND_VIEW', 518);

$app = new Application(APP_CONFIG, APP_MODE);
$app->bootstrap()->run();