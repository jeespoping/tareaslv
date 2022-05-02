<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use \Illuminate\Support\Facades\Auth;

Route::middleware('auth:sanctum')->get('/validartoken', function (Request $request) {
    return response()->json([
        'user' => [
            'codigo' => Auth::user()->Codigo,
            'descripcion' => Auth::user()->Descripcion
        ],
        'res' => true
    ]);
});

Route::post('/loginauna', 'AuthController@loginAuna')->middleware('guest:sanctum');
Route::post('/login', 'AuthController@login')->middleware('guest:sanctum');
