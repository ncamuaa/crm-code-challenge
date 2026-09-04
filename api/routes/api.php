<?php

/** @var \Laravel\Lumen\Routing\Router $router */

$router->get('/health', function () {
    return response()->json(['status' => 'ok']);
});

$router->group(['prefix' => 'customers'], function () use ($router) {
    $router->get('/', 'CustomerController@index');       // list + search (?q=, ?field=)
    $router->post('/', 'CustomerController@store');      // create
    $router->get('/{id}', 'CustomerController@show');    // view one
    $router->put('/{id}', 'CustomerController@update');  // update
    $router->patch('/{id}', 'CustomerController@update');
    $router->delete('/{id}', 'CustomerController@destroy'); // delete
});
