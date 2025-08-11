<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    // Registro de usuario
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'     => 'required|string',
            'email'    => 'required|email|unique:usuarios,email',
            'passwordd'=> 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $usuario = Usuario::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'passwordd'=> Hash::make($request->passwordd),
            'role'     => 'user', // Rol por defecto
        ]);

        return response()->json([
            'message' => 'Usuario registrado correctamente',
            'usuario' => $usuario
        ], 201);
    }

    // Login de usuario
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'     => 'required|email',
            'passwordd' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $usuario = Usuario::where('email', $request->email)->first();

        if (!$usuario || !Hash::check($request->passwordd, $usuario->passwordd)) {
            return response()->json(['error' => 'Credenciales inválidas'], 401);
        }

        // Crear token con nombre descriptivo
        $deviceName = $request->header('User-Agent') ? 
            substr($request->header('User-Agent'), 0, 100) : 
            'Dispositivo desconocido';
            
        $token = $usuario->createToken($deviceName)->plainTextToken;

        return response()->json([
            'message' => 'Login exitoso',
            'usuario' => $usuario,
            'token'   => $token,
            'role'    => $usuario->role,
        ]);
    }

    // Logout de usuario
    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
        
        return response()->json([
            'message' => 'Logout exitoso'
        ]);
    }

    // Obtener perfil del usuario autenticado
    public function perfil(Request $request)
    {
        return response()->json($request->user());
    }

    // Actualizar perfil del usuario autenticado
    public function updatePerfil(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'string|max:255',
            'email' => 'email|unique:usuarios,email,' . $request->user()->id_huesped . ',id_huesped',
            'passwordd' => 'string|min:6|nullable',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $usuario = $request->user();
        
        if ($request->has('name')) {
            $usuario->name = $request->name;
        }
        
        if ($request->has('email')) {
            $usuario->email = $request->email;
        }
        
        if ($request->has('passwordd') && $request->passwordd) {
            $usuario->passwordd = Hash::make($request->passwordd);
        }
        
        $usuario->save();

        return response()->json([
            'message' => 'Perfil actualizado correctamente',
            'usuario' => $usuario
        ]);
    }

    // Cambiar contraseña y cerrar todas las sesiones
    public function cambiarPassword(Request $request)
    {
        try {
            $request->validate([
                'password_actual' => 'required|string',
                'password_nuevo' => 'required|string|min:8',
                'password_nuevo_confirmation' => 'required|string|same:password_nuevo',
            ]);

            $usuario = $request->user();

            // Verificar contraseña actual (ahora puede usar password gracias al accessor)
            if (!Hash::check($request->password_actual, $usuario->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'La contraseña actual es incorrecta'
                ], 400);
            }

            // Actualizar contraseña (ahora puede usar password gracias al mutator)
            $usuario->password = Hash::make($request->password_nuevo);
            $usuario->save();

            // Revocar todos los tokens existentes (cerrar todas las sesiones)
            $usuario->tokens()->delete();

            return response()->json([
                'success' => true,
                'message' => 'Contraseña actualizada correctamente. Se han cerrado todas las sesiones activas.'
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

    // Obtener sesiones activas del usuario
    public function sesionesActivas(Request $request)
    {
        $usuario = $request->user();
        $tokens = $usuario->tokens()
            ->select('id', 'name', 'last_used_at', 'created_at')
            ->get()
            ->map(function ($token) {
                return [
                    'id' => $token->id,
                    'nombre' => $token->name,
                    'ultimo_uso' => $token->last_used_at,
                    'creado_en' => $token->created_at,
                    'es_actual' => $token->last_used_at && 
                                  $token->last_used_at->diffInMinutes(now()) < 5
                ];
            });

        return response()->json([
            'sesiones' => $tokens,
            'total' => $tokens->count()
        ]);
    }

    // Cerrar sesión específica
    public function cerrarSesion(Request $request, $tokenId)
    {
        $usuario = $request->user();
        $token = $usuario->tokens()->where('id', $tokenId)->first();

        if (!$token) {
            return response()->json(['error' => 'Sesión no encontrada'], 404);
        }

        $token->delete();

        return response()->json([
            'message' => 'Sesión cerrada correctamente'
        ]);
    }

    // Cerrar todas las demás sesiones (excepto la actual)
    public function cerrarOtrasSesiones(Request $request)
    {
        $usuario = $request->user();
        $tokenActual = $request->bearerToken();
        
        // Obtener el token actual
        $currentToken = $usuario->tokens()
            ->where('token', hash('sha256', $tokenActual))
            ->first();
        
        if ($currentToken) {
            // Eliminar todos los tokens excepto el actual
            $usuario->tokens()->where('id', '!=', $currentToken->id)->delete();
        } else {
            // Si no se encuentra el token actual, eliminar todos
            $usuario->tokens()->delete();
        }

        return response()->json([
            'message' => 'Todas las demás sesiones han sido cerradas'
        ]);
    }
}