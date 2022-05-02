<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use \Illuminate\Support\Facades\Auth;

Route::get('/MaestrosMatrix/permisos', 'MaestrosMatrixController@permisos')->middleware('auth:sanctum');
Route::post('/MaestrosMatrix/datos', 'MaestrosMatrixController@datos')->middleware('auth:sanctum');

Route::post('MaestrosMatrix/selects', 'MaestrosMatrixController@selects')->middleware('auth:sanctum');
Route::post('MaestrosMatrix/relations', 'MaestrosMatrixController@relaciones')->middleware('auth:sanctum');
Route::post('MaestrosMatrix/agregar', 'MaestrosMatrixController@agregar')->middleware('auth:sanctum');
Route::post('MaestrosMatrix/actualizar', 'MaestrosMatrixController@update')->middleware('auth:sanctum');
Route::post('MaestrosMatrix/eliminar', 'MaestrosMatrixController@delete')->middleware('auth:sanctum');
