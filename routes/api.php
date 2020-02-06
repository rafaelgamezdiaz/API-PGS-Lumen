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


$router->group(['prefix' => 'pay', 'middleware' => 'auth'], function () use ($router) {  // , 'middleware' => 'auth'

    // PAYMENT ROUTES
    $router->get('payments/', 'Payment\PaymentController@index');
    $router->post('payments/', 'Payment\PaymentController@store');
    $router->get('payments/{id}', 'Payment\PaymentController@show');
    $router->put('payments/{id}', 'Payment\PaymentController@update');
    $router->patch('payments/{id}', 'Payment\PaymentController@update');
    $router->delete('payments/{id}', 'Payment\PaymentController@destroy');

    // PAYMENTS BY CLIENT
    $router->get('payments/{id}/client', 'Payment\PaymentClientController@index');

    // BILLS ROUTES
    $router->get('bills/', 'Bill\BillController@index');
    $router->post('bills/', 'Bill\BillController@store');
    $router->get('bills/{id}', 'Bill\BillController@show');
    $router->put('bills/{id}', 'Bill\BillController@update');
    $router->patch('bills/{id}', 'Bill\BillController@update');
    $router->delete('bills/{id}', 'Bill\BillController@destroy');

    // METHODS ROUTES (Manage methods as "Tarjeta", "Efectivo", "Transferencia", ...)
    $router->get('methods', 'Method\MethodController@index');

    // PAYMENT TYPE (Manage types as "A Enviar", "A Recibir")
    $router->get('type', 'Type\TypeController@index');

    // REPORT
    $router->post('report', 'Report\ReportController@automatic');


} );
