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

Route::get('/home', function () {
    return view('welcome');
});


Route::get('/', 'MainController@qwe');
Route::post('/', 'MainController@qwe');

Route::get('/update', 'MainController@qwe');
Route::post('/update', 'MainController@qwe');

Route::post('/528975393:AAGixyvKXmLFEDBcEBjeqXL3-WxPYq41RvQ/webhook', 'MainController@get');
Route::put('/528975393:AAGixyvKXmLFEDBcEBjeqXL3-WxPYq41RvQ/webhook', 'MainController@get');
Route::get('/528975393:AAGixyvKXmLFEDBcEBjeqXL3-WxPYq41RvQ/webhook', 'MainController@get');

Route::any('{all}', function(){
    return 'Lol';
})->where('all', '.*');