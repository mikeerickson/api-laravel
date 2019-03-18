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

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

$middleware = [
    'api.logger',
    'api.rateLimit',
    'api.verify',
    'api'
];

Route::group(['prefix' => 'v1', 'middleware' => $middleware], function () {

    Route::get('/teams', ['as'=>'teams', 'uses'=>'TeamController@index']);
    Route::get('/teams/{id}', ['as'=>'teams.detail', 'uses'=>'TeamController@show']);
    Route::post('/teams', ['as'=>'teams.store', 'uses'=>'TeamController@store']);

});
