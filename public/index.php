<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

// Split the request lifecycle so we can close the FastCGI connection BEFORE
// running app()->terminating() callbacks.  This lets long-running tasks
// (e.g. identity sync) registered via app()->terminating() execute after
// the browser has already received its response — no NGINX fastcgi_read_timeout.
$kernel   = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request  = Request::capture();
$response = $kernel->handle($request);
$response->send();

// Close the FastCGI connection to NGINX.  Once called, NGINX forwards the
// complete response to the browser and stops waiting — terminating callbacks
// run freely without any HTTP timeout pressure.
if (function_exists('fastcgi_finish_request')) {
    fastcgi_finish_request();
}

$kernel->terminate($request, $response);
