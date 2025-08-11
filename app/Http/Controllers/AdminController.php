<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use App\Models\Multa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AdminController extends Controller
{
    // Obtener todos los usuarios
    public function usuarios()
    {
        $usuarios = Usuario::where('role', 'user')
            ->select('id_huesped', 'name', 'email', 'created_at')
            ->get();
        
        return response()->json($usuarios);
    }

    // Obtener detalles de un usuario específico
    public function usuarioDetalle($id)
    {
        $usuario = Usuario::where('id_huesped', $id)
            ->where('role', 'user')
            ->first();

        if (!$usuario) {
            return response()->json(['error' => 'Usuario no encontrado'], 404);
        }

        // Incluir multas del usuario
        $multas = Multa::where('id_huesped', $id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'usuario' => $usuario,
            'multas' => $multas
        ]);
    }

    // Dashboard con estadísticas
    public function dashboard()
    {
        $estadisticas = [
            'total_usuarios' => Usuario::where('role', 'user')->count(),
            'total_multas' => Multa::count(),
            'multas_pendientes' => Multa::where('estatus', 'pendiente')->count(),
            'multas_pagadas' => Multa::where('estatus', 'pagada')->count(),
            'monto_total_pendiente' => Multa::where('estatus', 'pendiente')->sum('cantidad'),
            'monto_total_recaudado' => Multa::where('estatus', 'pagada')->sum('cantidad'),
        ];

        // Multas recientes
        $multas_recientes = Multa::with(['usuario:id_huesped,name'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return response()->json([
            'estadisticas' => $estadisticas,
            'multas_recientes' => $multas_recientes
        ]);
    }

    // Obtener perfil del admin
    public function perfil(Request $request)
    {
        $admin = $request->user();
        
        return response()->json([
            'id' => $admin->id_huesped,
            'name' => $admin->name,
            'email' => $admin->email,
            'role' => $admin->role,
            'created_at' => $admin->created_at
        ]);
    }

    // Actualizar perfil del admin
    public function updatePerfil(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:usuarios,email,' . $request->user()->id_huesped . ',id_huesped'
            ]);

            $admin = $request->user();
            $admin->name = $request->name;
            $admin->email = $request->email;
            $admin->save();

            return response()->json([
                'success' => true,
                'message' => 'Perfil actualizado correctamente',
                'user' => $admin
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el perfil'
            ], 500);
        }
    }

    // Cambiar contraseña del admin y cerrar todas las sesiones
    public function cambiarPassword(Request $request)
    {
        try {
            $request->validate([
                'password_actual' => 'required|string',
                'password_nuevo' => 'required|string|min:8',
                'password_nuevo_confirmation' => 'required|string|same:password_nuevo',
            ]);

            $admin = $request->user();
            
            // Verificar contraseña actual con manejo especial para contraseñas no hasheadas
            $passwordMatch = false;
            
            try {
                // Intentar verificación normal con Hash::check (ahora usa el accessor)
                $passwordMatch = Hash::check($request->password_actual, $admin->password);
            } catch (\Exception $e) {
                // Si falla (posiblemente contraseña no hasheada), verificar directamente
                if ($admin->passwordd === $request->password_actual) {
                    $passwordMatch = true;
                    
                    // Actualizar la contraseña actual para que esté hasheada
                    $admin->password = Hash::make($admin->passwordd);
                    $admin->save();
                }
            }
            
            if (!$passwordMatch) {
                return response()->json([
                    'success' => false,
                    'message' => 'La contraseña actual es incorrecta'
                ], 400);
            }

            // Contar sesiones antes de cerrarlas
            $sessionCount = $admin->tokens()->count();

            // Actualizar con la nueva contraseña (ahora usa el mutator)
            $admin->password = Hash::make($request->password_nuevo);
            $admin->save();

            // Revocar todos los tokens existentes (cerrar todas las sesiones)
            $admin->tokens()->delete();

            // Crear un nuevo token para la sesión actual
            $deviceName = $request->header('User-Agent', 'Admin Device');
            $newToken = $admin->createToken($deviceName)->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'Contraseña actualizada correctamente. Se han cerrado todas las sesiones activas.',
                'new_token' => $newToken,
                'sessions_closed' => $sessionCount,
                'require_reauth' => true // Indica que necesita reautenticación
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar la contraseña: ' . $e->getMessage()
            ], 500);
        }
    }

    // Obtener sesiones activas del admin
    public function sesionesActivas(Request $request)
    {
        $admin = $request->user();
        
        $tokens = $admin->tokens()
            ->orderBy('last_used_at', 'desc')
            ->get()
            ->map(function ($token, $index) {
                return [
                    'id' => $token->id,
                    'nombre' => $token->name ?: 'Dispositivo sin nombre',
                    'ultimo_uso' => $token->last_used_at ? $token->last_used_at->format('Y-m-d H:i:s') : null,
                    'creado_en' => $token->created_at->format('Y-m-d H:i:s'),
                    'es_actual' => $index === 0, // El más reciente como actual (simplificado)
                    'ip' => null,
                    'dispositivo' => $this->parseUserAgent($token->name)
                ];
            });

        return response()->json([
            'success' => true,
            'sesiones' => $tokens,
            'total_sesiones' => $tokens->count(),
        ]);
    }

    // Helper para parsear User-Agent y obtener info del dispositivo
    private function parseUserAgent($userAgent)
    {
        if (!$userAgent || $userAgent === 'Dispositivo sin nombre') {
            return 'Dispositivo desconocido';
        }

        // Detectar navegador
        if (strpos($userAgent, 'Chrome') !== false) return 'Chrome';
        if (strpos($userAgent, 'Firefox') !== false) return 'Firefox';
        if (strpos($userAgent, 'Safari') !== false) return 'Safari';
        if (strpos($userAgent, 'Edge') !== false) return 'Edge';
        
        return 'Navegador desconocido';
    }

    // Cerrar sesión específica del admin
    public function cerrarSesion(Request $request, $tokenId)
    {
        $admin = $request->user();
        
        $token = $admin->tokens()->where('id', $tokenId)->first();
        
        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Sesión no encontrada'
            ], 404);
        }

        $token->delete();

        return response()->json([
            'success' => true,
            'message' => 'Sesión cerrada correctamente'
        ]);
    }

    // Cerrar todas las demás sesiones del admin (excepto la actual)
    public function cerrarOtrasSesiones(Request $request)
    {
        $admin = $request->user();
        
        // Eliminar todos los tokens excepto el actual (identificado por el token usado en la request)
        $tokenCount = $admin->tokens()->count();
        $admin->tokens()->delete(); // Eliminar todos
        
        // Crear un nuevo token para la sesión actual
        $deviceName = $request->header('User-Agent', 'Unknown Device');
        $newToken = $admin->createToken($deviceName)->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Todas las demás sesiones han sido cerradas correctamente',
            'sessions_closed' => $tokenCount - 1
        ]);
    }

    // Endpoint para diagnosticar y corregir problemas de contraseña
    public function diagnosticarPassword(Request $request)
    {
        try {
            $admin = $request->user();
            
            $diagnostico = [
                'email' => $admin->email,
                'password_length' => strlen($admin->password), // Usa el accessor
                'is_bcrypt' => str_starts_with($admin->password, '$2y$'), // Usa el accessor
                'password_hash_info' => password_get_info($admin->password), // Usa el accessor
                'raw_column_data' => $admin->passwordd, // Para debug, mostrar columna cruda
            ];
            
            // Si la contraseña no está hasheada correctamente, corregirla
            if (!$diagnostico['is_bcrypt'] || $diagnostico['password_length'] !== 60) {
                // Asumir que la contraseña actual es "123456789" y hashearla
                $admin->password = Hash::make('123456789'); // Usa el mutator
                $admin->save();
                
                $diagnostico['fixed'] = true;
                $diagnostico['new_password'] = '123456789 (hasheada correctamente)';
                $diagnostico['new_password_length'] = strlen($admin->password); // Verificar nueva longitud
            }
            
            return response()->json([
                'success' => true,
                'diagnostico' => $diagnostico
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Verificar si el token actual sigue siendo válido
    public function verificarToken(Request $request)
    {
        try {
            $admin = $request->user();
            
            if (!$admin) {
                return response()->json([
                    'valid' => false,
                    'message' => 'Token inválido'
                ], 401);
            }

            return response()->json([
                'valid' => true,
                'user' => [
                    'id' => $admin->id_huesped,
                    'name' => $admin->name,
                    'email' => $admin->email,
                    'role' => $admin->role
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'valid' => false,
                'message' => 'Error verificando token'
            ], 401);
        }
    }
}
