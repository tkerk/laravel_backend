<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use\App\Models\User;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

use App\Http\Controllers\MultaControlador;

Route::post('/multas', [MultaControlador::class, 'store']);


Route::get('/multas/recientes', [MultaControlador::class, 'nuevasmultas']);

Route::post('/login', funcion (Request $request){
    $user = User::find(100);

    $token= $user->createToken('token')->plainTextToken;
    return response()->json(['token' => $token],
        200);
    });


    Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
        return 'Hola Mundo';
    });


