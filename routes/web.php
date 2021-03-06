<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

$middleware = [
//    'api.logger',
//    'api.rateLimit',
//    'api.verify',
//    'api'
];

Route::group(['prefix' => 'api/v1', 'middleware' => $middleware], function () {

    Route::get('/widgets', ['as' => 'widgets', 'uses' => 'WidgetController@index']);
    Route::get('/widgets/{id}', ['as' => 'widgets.detail', 'uses' => 'WidgetController@show']);
//    Route::post('/teams', ['as' => 'teams.store', 'uses' => 'TeamController@store']);

});

Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');
