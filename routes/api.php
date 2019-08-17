<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group([
    'prefix' => 'v1',
    'namespace' => 'Api\v1',

], function ($router) {

  $router->group([
      'middleware' => 'api_auth', // Custom auth middleware
  ], function ($router) {
    // authentication related apis
    $router->post('/login','AuthContrller@login');
    $router->post('/logout','AuthContrller@logout');
    //end worker only

  });

  Route::post('/get-token','AuthContrller@getToken');

});
