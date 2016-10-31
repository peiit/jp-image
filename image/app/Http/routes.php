<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

// $app->get('/', function () use ($app) {
//     return $app->version();
// });

$app->get('/', 'IndexController@index');
$app->get('/import', 'IndexController@import');
$app->post('/upload', 'IndexController@upload');
$app->get('/resize/{w}/{h}/{mode}/{module}/{id}', 'IndexController@resizeWithModule');
$app->get('/resize/{w}/{h}/{mode}/{id}', 'IndexController@resize');
$app->get('/crop/{x}/{y}/{w}/{h}/{module}/{id}', 'IndexController@cropWithModule');
$app->get('/crop/{x}/{y}/{w}/{h}/{id}', 'IndexController@crop');
