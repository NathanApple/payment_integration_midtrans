<?php

/** @var \Laravel\Lumen\Routing\Router $router */

use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductController;
use Illuminate\Http\Request;

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

$router->get('/', function () use ($router) {
    return $router->app->version();
});

$router->get('/test', 'PaymentController@test');

$router->group(['prefix'=>'api'], function() use ($router){

    // Products
    $router->get('/products', 'ProductController@index');
    $router->get('/product/{id}', 'ProductController@show');

    $router->group(['middleware' => 'auth'], function() use ($router){
        $router->post('/product', 'ProductController@create');
        $router->put('/product/{id}', 'ProductController@update');
        $router->delete('/product/{id}', 'ProductController@destroy');

        // Payment
        $router->group(['prefix'=>'payment'], function() use ($router){
            $router->post('/create', 'PaymentController@create');
            $router->post('/check', 'PaymentController@check_status');
        });
    });

    // Midtrans Notification
    $router->post('/midtrans/notification', 'PaymentController@notification');

    $router->post('/login', 'LoginController@login');
    $router->post('/register', 'LoginController@register');



});


