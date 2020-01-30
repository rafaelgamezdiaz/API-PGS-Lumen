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


$router->group(['prefix' => 'sub'], function () use ($router) {  // , 'middleware' => 'auth'

    // PAYMENT ROUTES
    $router->get('payments/', 'Payment\PaymentController@index');
    $router->post('payments/', 'Payment\PaymentController@store');

    // PAYMENT METHOD
    $router->get('payments/methods', 'Method\MethodController@index');

    // PAYMENT TYPE
    $router->get('payments/type', 'Type\TypeController@index');
} );
