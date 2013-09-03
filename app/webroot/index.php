<?php

use app\Application;

error_reporting(E_ALL);

require dirname(__DIR__) . '/config/bootstrap.php';

Application::getInstance()->run();