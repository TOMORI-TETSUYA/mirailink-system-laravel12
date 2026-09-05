<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

$applicationPath = dirname(__DIR__).'/life-Insurance_app';

if (file_exists($maintenance = $applicationPath.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

require $applicationPath.'/vendor/autoload.php';

/** @var Application $app */
$app = require_once $applicationPath.'/bootstrap/app.php';

$app->handleRequest(Request::capture());
