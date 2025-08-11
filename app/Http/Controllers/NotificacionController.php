<?php

namespace App\Http\Controllers;

use App\Models\Notificacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificacionController extends Controller
{
    // Obtener notificaciones para el usuario autenticado (funciona para admin y huésped)
    public function misNotificaciones()
    {
        $usuario = Auth::user();
        
        if ($usuario->role === 'admin') {
            // Si el admin tiene un id_huesped específico, mostrar sus notificaciones personales
            if ($usuario->id_huesped) {
                $notificaciones = Notificacion::where('id_huesped', $usuario->id_huesped)
                    ->orderBy('created_at', 'desc')
                    ->get();
            } else {
                // Admin sin id_huesped ve notificaciones del sistema (tipo admin)
                $notificaciones = Notificacion::where('tipo', 'admin')
                    ->orWhere('tipo', 'sistema') // Si tienes notificaciones de sistema
                    ->orderBy('created_at', 'desc')
                    ->get();
            }
        } else {
            // Huéspedes ven solo sus notificaciones
            $notificaciones = Notificacion::where('id_huesped', $usuario->id_huesped)
                ->orderBy('created_at', 'desc')
                ->get();
        }
        
        return response()->json($notificaciones);
    }

    // Obtener notificaciones para el admin
    public function notificacionesAdmin()
    {
        $notificaciones = Notificacion::with(['usuario:id_huesped,name'])
            ->where('tipo', 'admin') // Solo notificaciones dirigidas al admin
            ->orderBy('created_at', 'desc')
            ->get();
        
        return response()->json($notificaciones);
    }

    // Marcar notificación como leída (funciona para admin y huésped)
    public function marcarComoLeida($id)
    {
        $usuario = Auth::user();
        
        if ($usuario->role === 'admin') {
            if ($usuario->id_huesped) {
                // Admin con id_huesped específico
                $notificacion = Notificacion::where('id', $id)
                    ->where('id_huesped', $usuario->id_huesped)
                    ->first();
            } else {
                // Admin sin id_huesped específico puede marcar notificaciones de tipo admin/sistema
                $notificacion = Notificacion::where('id', $id)
                    ->where(function($query) {
                        $query->where('tipo', 'admin')
                              ->orWhere('tipo', 'sistema');
                    })
                    ->first();
            }
        } else {
            $notificacion = Notificacion::where('id', $id)
                ->where('id_huesped', $usuario->id_huesped)
                ->first();
        }

        if (!$notificacion) {
            return response()->json(['error' => 'Notificación no encontrada'], 404);
        }

        $notificacion->leida = true;
        $notificacion->save();

        return response()->json(['message' => 'Notificación marcada como leída']);
    }

    // Crear notificación (usado internamente)
    public static function crearNotificacion($data)
    {
        return Notificacion::create($data);
    }
}
