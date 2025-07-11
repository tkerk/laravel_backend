<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\MultaControlador;


Route::post('/register', [UserController::class, 'register']);
Route::post('/login', [UserController::class, 'login']);       
Route::get('/usuarios', [UserController::class, 'list']); 


Route::post('/multas', [MultaControlador::class, 'store']);                        
Route::get('/multas/huesped/{id}', [MultaControlador::class, 'multasPorHuesped']); 
Route::get('/multas/reciente/{id}', [MultaControlador::class, 'multaRecientePorHuesped']); 
Route::post('/multas/{id}/visualizar', [MultaControlador::class, 'marcarComoVisualizada']); 