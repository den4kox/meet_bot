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
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Client;
use App\Http\Middleware\CheckLastUpdate;
Route::get('/home', function () {
    return view('welcome');
});

Route::post('telegram/handler', 'MainController@handler'); //->middleware(CheckLastUpdate::class);

Route::post('/setGeneralTable', 'MainController@setGeneralTable');

Route::post('setHook', 'MainController@setHook');