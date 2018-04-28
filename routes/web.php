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

Route::get('/home', function () {
    return view('welcome');
});


Route::get('/', 'MainController@qwe');
Route::post('/', 'MainController@qwe');

Route::get('/update', 'MainController@qwe');
Route::post('/update', 'MainController@qwe');

Route::post('/528975393:AAGixyvKXmLFEDBcEBjeqXL3-WxPYq41RvQ/webhook', 'MainController@get1');
Route::post('528975393:AAGixyvKXmLFEDBcEBjeqXL3-WxPYq41RvQ/webhook', 'MainController@get2');
Route::get('/528975393:AAGixyvKXmLFEDBcEBjeqXL3-WxPYq41RvQ/webhook', 'MainController@get3');

Route::any('{all}', function(){
    $apiKey = '528975393:AAGixyvKXmLFEDBcEBjeqXL3-WxPYq41RvQ'; // Put your bot's API key here
    $apiURL = 'https://api.telegram.org/bot' . $apiKey . '/';
    $client = new Client( array( 'base_uri' => $apiURL ) );
    
    $resp = $client->post('sendMessage', 
        array( 'query' => array( 'chat_id' => '-1001395709569', 'text' => "Янка забиянка" ) ) 
    );
    return 'Lol';
})->where('all', '.*');