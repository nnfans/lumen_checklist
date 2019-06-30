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


$router->group(['middleware' => 'auth'], function () use ($router) {
    $router->get('/', function () use ($router) {
        return $router->app->version();
    });

    $router->get('/checklists', 'ChecklistController@list');
    $router->post('/checklists', 'ChecklistController@create');
    $router->get('/checklists/{checklistId}', 'ChecklistController@find');
    $router->patch('/checklists/{checklistId}', 'ChecklistController@update');
    $router->delete('/checklists/{checklistId}', 'ChecklistController@destroy');
});