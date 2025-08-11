<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\MultaControlador;
use App\Http\Controllers\NotificacionController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\PasswordResetController;

// Rutas públicas
Route::post('/register', [UserController::class, 'register']);
Route::post('/login', [UserController::class, 'login']);
Route::post('/logout', [UserController::class, 'logout'])->middleware('auth:sanctum');

// Rutas para recuperación de contraseña (públicas)
Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink']);
Route::post('/reset-password', [PasswordResetController::class, 'resetPassword']);
Route::post('/validate-reset-token', [PasswordResetController::class, 'validateResetToken']);

// Rutas compartidas para usuarios autenticados
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/perfil', [UserController::class, 'perfil']);
    Route::put('/perfil', [UserController::class, 'updatePerfil']);
    Route::post('/cambiar-password', [UserController::class, 'cambiarPassword']);
    Route::get('/sesiones-activas', [UserController::class, 'sesionesActivas']);
    Route::delete('/cerrar-sesion/{tokenId}', [UserController::class, 'cerrarSesion']);
    Route::post('/cerrar-otras-sesiones', [UserController::class, 'cerrarOtrasSesiones']);
    
    // Verificar token válido para ambos roles
    Route::get('/verificar-token', [AdminController::class, 'verificarToken']);
    
    // Rutas universales de multas y notificaciones (funcionan para admin y huésped)
    Route::get('/mis-multas', [MultaControlador::class, 'misMultas']);
    Route::get('/mi-multa-reciente', [MultaControlador::class, 'miMultaReciente']);
    Route::post('/multas/{id}/visualizar', [MultaControlador::class, 'marcarComoVisualizada']);
    Route::get('/mis-notificaciones', [NotificacionController::class, 'misNotificaciones']);
    Route::post('/notificaciones/{id}/marcar-leida', [NotificacionController::class, 'marcarComoLeida']);
});

// Rutas exclusivas para admin
Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    // Gestión de perfil y sesiones del admin
    Route::get('/admin/perfil', [AdminController::class, 'perfil']);
    Route::put('/admin/perfil', [AdminController::class, 'updatePerfil']);
    Route::post('/admin/cambiar-password', [AdminController::class, 'cambiarPassword']);
    Route::get('/admin/sesiones-activas', [AdminController::class, 'sesionesActivas']);
    Route::delete('/admin/cerrar-sesion/{tokenId}', [AdminController::class, 'cerrarSesion']);
    Route::post('/admin/cerrar-otras-sesiones', [AdminController::class, 'cerrarOtrasSesiones']);
    Route::get('/admin/diagnosticar-password', [AdminController::class, 'diagnosticarPassword']);
    
    // Gestión de usuarios
    Route::get('/admin/usuarios', [AdminController::class, 'usuarios']);
    Route::get('/admin/usuarios/{id}', [AdminController::class, 'usuarioDetalle']);
    
    // Gestión de multas (crear, ver todas)
    Route::post('/admin/multas', [MultaControlador::class, 'store']);
    Route::get('/admin/multas', [MultaControlador::class, 'todasLasMultas']);
    Route::put('/admin/multas/{id}', [MultaControlador::class, 'update']);
    Route::delete('/admin/multas/{id}', [MultaControlador::class, 'destroy']);
    
    // Notificaciones del admin
    Route::get('/admin/notificaciones', [NotificacionController::class, 'notificacionesAdmin']);
    Route::post('/admin/notificaciones/{id}/marcar-leida', [NotificacionController::class, 'marcarComoLeida']);
    
    // Estadísticas
    Route::get('/admin/dashboard', [AdminController::class, 'dashboard']);
});

// Rutas para usuarios huéspedes
Route::middleware(['auth:sanctum', 'user'])->group(function () {
    // Ver solo sus propias multas
    Route::get('/huesped/multas', [MultaControlador::class, 'misMultas']);
    Route::get('/huesped/multas/reciente', [MultaControlador::class, 'miMultaReciente']);
    Route::post('/huesped/multas/{id}/visualizar', [MultaControlador::class, 'marcarComoVisualizada']);
    
    // Notificaciones del huésped
    Route::get('/huesped/notificaciones', [NotificacionController::class, 'misNotificaciones']);
    Route::post('/huesped/notificaciones/{id}/marcar-leida', [NotificacionController::class, 'marcarComoLeida']);
});