<?php
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
define('LARAVEL_START', microtime(true));
$basePath = '/home/u641948971/domains/devalejandre.com/apps/service-desk';
if (is_file($maintenance = $basePath.'/storage/framework/maintenance.php')) require $maintenance;
require $basePath.'/vendor/autoload.php';
/** @var Application $app */
$app = require_once $basePath.'/bootstrap/app.php';
$app->handleRequest(Request::capture());
