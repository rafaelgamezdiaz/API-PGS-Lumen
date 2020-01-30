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


$router->group(['prefix' => 'pa', 'middleware' => 'auth'], function () use ($router) {  // , 'middleware' => 'auth'

    // PAYMENT ROUTES
    $router->get('payments/', 'Payment\PaymentController@index');
    $router->post('payments/', 'Payment\PaymentController@store');
    $router->get('payments/{id}', 'Payment\PaymentController@show');
    $router->put('payments/{id}', 'Payment\PaymentController@update');
    $router->patch('payments/{id}', 'Payment\PaymentController@update');

    // PAYMENT METHOD
    $router->get('methods', 'Method\MethodController@index');

    // PAYMENT TYPE
    $router->get('type', 'Type\TypeController@index');
} );
