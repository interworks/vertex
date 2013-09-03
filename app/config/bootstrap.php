<?php

use app\Application;

define('APPLICATION_NAME', 'kkytbs');
define('APPLICATION_PATH', realpath(dirname(__DIR__)));

defined('APPLICATION_ENV')
    || define('APPLICATION_ENV', (getenv('APPLICATION_ENV') ?: 'localdevelopment'));

define('VOX_LIBRARY_PATH', realpath(APPLICATION_PATH . '/../libraries'));

require __DIR__ . '/bootstrap/libraries.php';
require __DIR__ . '/bootstrap/cache.php';

$application = new Application(APPLICATION_ENV);
$application->bootstrap();
