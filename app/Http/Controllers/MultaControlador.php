<?php

namespace App\Http\Controllers;

use App\Models\Multa;
use App\Http\Controllers\NotificacionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class MultaControlador extends Controller
{
   // Crear multa (solo admin)
   public function store(Request $request)
   {
       $validator = Validator::make($request->all(), [
           'id_huesped' => 'required|exists:usuarios,id_huesped',
           'cantidad' => 'required|numeric|min:0',
           'razon' => 'required|string|max:255',
           'emitido_a_las' => 'required|date',
           'estatus' => 'required|in:pendiente,pagada',
       ]);

       if ($validator->fails()) {
           return response()->json($validator->errors(), 422);
       }

       $multa = Multa::create([
           'id_huesped' => $request->id_huesped,
           'cantidad' => $request->cantidad,
           'razon' => $request->razon,
           'emitido_a_las' => $request->emitido_a_las,
           'estatus' => $request->estatus,
           'notificado_en' => now(),
       ]);

       // Crear notificación para el huésped
       NotificacionController::crearNotificacion([
           'id_huesped' => $request->id_huesped,
           'titulo' => 'Nueva multa recibida',
           'mensaje' => "Se le ha asignado una multa por: {$request->razon}. Monto: $" . number_format($request->cantidad, 2),
           'tipo' => 'huesped',
           'multa_id' => $multa->id,
       ]);

       return response()->json($multa, 201);
   }

   // Obtener todas las multas (solo admin)
   public function todasLasMultas()
   {
       $multas = Multa::with(['usuario:id_huesped,name'])
           ->orderBy('created_at', 'desc')
           ->get();
       
       return response()->json($multas);
   }

   // Actualizar multa (solo admin)
   public function update(Request $request, $id)
   {
       $multa = Multa::findOrFail($id);
       
       $validator = Validator::make($request->all(), [
           'cantidad' => 'numeric|min:0',
           'razon' => 'string|max:255',
           'estatus' => 'in:pendiente,pagada',
       ]);

       if ($validator->fails()) {
           return response()->json($validator->errors(), 422);
       }

       $multa->update($request->only(['cantidad', 'razon', 'estatus']));

       return response()->json($multa);
   }

   // Eliminar multa (solo admin)
   public function destroy($id)
   {
       $multa = Multa::findOrFail($id);
       $multa->delete();

       return response()->json(['message' => 'Multa eliminada correctamente']);
   }

   // Obtener multas del usuario autenticado (funciona para admin y huésped)
   public function misMultas()
   {
       $usuario = Auth::user();
       
       // Si es admin, puede ver todas las multas asignadas a él o todas si no tiene id_huesped específico
       if ($usuario->role === 'admin') {
           // Si el admin tiene un id_huesped, mostrar solo esas multas
           if ($usuario->id_huesped) {
               $multas = Multa::where('id_huesped', $usuario->id_huesped)
                   ->orderBy('notificado_en', 'desc')
                   ->get();
           } else {
               // Si no tiene id_huesped específico, puede ver todas las multas (para propósitos administrativos)
               $multas = Multa::with(['usuario:id_huesped,name'])
                   ->orderBy('notificado_en', 'desc')
                   ->get();
           }
       } else {
           // Para huéspedes normales, solo sus multas
           $multas = Multa::where('id_huesped', $usuario->id_huesped)
               ->orderBy('notificado_en', 'desc')
               ->get();
       }
       
       return response()->json($multas);
   }

   // Obtener la multa más reciente del usuario autenticado
   public function miMultaReciente()
   {
       $usuario = Auth::user();
       
       if ($usuario->role === 'admin') {
           if ($usuario->id_huesped) {
               $multa = Multa::where('id_huesped', $usuario->id_huesped)
                   ->orderBy('notificado_en', 'desc')
                   ->first();
           } else {
               // Admin sin id_huesped específico ve la multa más reciente del sistema
               $multa = Multa::with(['usuario:id_huesped,name'])
                   ->orderBy('notificado_en', 'desc')
                   ->first();
           }
       } else {
           $multa = Multa::where('id_huesped', $usuario->id_huesped)
               ->orderBy('notificado_en', 'desc')
               ->first();
       }
       
       return response()->json($multa);
   }

   // Marcar multa como visualizada (funciona para admin y huésped)
   public function marcarComoVisualizada($id)
   {
       $usuario = Auth::user();
       
       if ($usuario->role === 'admin') {
           // Admin puede marcar cualquier multa como visualizada si tiene id_huesped o todas si es admin sin id_huesped
           if ($usuario->id_huesped) {
               $multa = Multa::where('id', $id)
                   ->where('id_huesped', $usuario->id_huesped)
                   ->first();
           } else {
               // Admin sin id_huesped específico puede marcar cualquier multa
               $multa = Multa::find($id);
           }
       } else {
           $multa = Multa::where('id', $id)
               ->where('id_huesped', $usuario->id_huesped)
               ->first();
       }

       if (!$multa) {
           return response()->json(['error' => 'Multa no encontrada'], 404);
       }

       $multa->visualizado = true;
       $multa->save();
       
       return response()->json(['success' => true]);
   }

   // MÉTODOS LEGACY - Mantener para compatibilidad
   public function multaRecientePorHuesped($id)
   {
       $multa = Multa::where('id_huesped', $id)->orderBy('notificado_en', 'desc')->first();
       return response()->json($multa);
   }

   public function multasPorHuesped($id)
   {
       $multas = Multa::where('id_huesped', $id)
           ->orderBy('notificado_en', 'desc')
           ->get();
       return response()->json($multas);
   }
}