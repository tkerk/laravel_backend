<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Str;
use App\Models\Usuario;

class PasswordResetController extends Controller
{
    /**
     * Enviar enlace de recuperación de contraseña
     */
    public function sendResetLink(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email inválido',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Buscar el usuario por email
            $user = Usuario::where('email', $request->email)->first();

            // Siempre responder de forma genérica por seguridad
            if (!$user) {
                return response()->json([
                    'success' => true,
                    'message' => 'Si el correo electrónico existe en nuestros registros, recibirás un enlace de recuperación.'
                ]);
            }

            // Generar código de 6 dígitos en lugar de token largo
            $code = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
            
            // Guardar en la tabla de reset tokens
            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $request->email],
                [
                    'token' => Hash::make($code),
                    'created_at' => now()
                ]
            );

            // Enviar código por email
            Mail::raw(
                "Tu código de recuperación de contraseña es: {$code}\n\n" .
                "Este código expira en 60 minutos.\n\n" .
                "Si no solicitaste este cambio, ignora este correo.\n\n" .
                "Saludos,\nEquipo WithDomine",
                function ($message) use ($request) {
                    $message->to($request->email)
                           ->subject('Código de Recuperación - WithDomine')
                           ->from(config('mail.from.address'), config('mail.from.name'));
                }
            );

            return response()->json([
                'success' => true,
                'message' => 'Si el correo electrónico existe en nuestros registros, recibirás un código de recuperación.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar la solicitud. Intenta nuevamente.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Restablecer contraseña usando token
     */
    public function resetPassword(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'code' => 'required|string|size:6',
                'passwordd' => 'required|string|min:8|confirmed'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Validar código manualmente
            $resetRecord = DB::table('password_reset_tokens')
                ->where('email', $request->email)
                ->first();

            if (!$resetRecord) {
                return response()->json([
                    'success' => false,
                    'message' => 'Código inválido o no encontrado.'
                ], 400);
            }

            // Verificar que el código sea correcto
            if (!Hash::check($request->code, $resetRecord->token)) {
                return response()->json([
                    'success' => false,
                    'message' => 'El código ingresado es incorrecto.'
                ], 400);
            }

            // Verificar que no haya expirado (60 minutos)
            $createdAt = new \DateTime($resetRecord->created_at);
            $now = new \DateTime();
            $diffInMinutes = $now->diff($createdAt)->i + ($now->diff($createdAt)->h * 60);
            
            if ($diffInMinutes > 60) {
                return response()->json([
                    'success' => false,
                    'message' => 'El código ha expirado. Solicita uno nuevo.'
                ], 400);
            }

            // Buscar usuario
            $user = Usuario::where('email', $request->email)->first();
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado.'
                ], 400);
            }

            // Actualizar la contraseña
            $user->passwordd = Hash::make($request->passwordd);
            $user->setRememberToken(Str::random(60));
            $user->save();

            // Revocar todos los tokens de Sanctum (cerrar todas las sesiones API)
            $user->tokens()->delete();

            // Eliminar el código usado
            DB::table('password_reset_tokens')
                ->where('email', $request->email)
                ->delete();

            event(new PasswordReset($user));

            return response()->json([
                'success' => true,
                'message' => 'Tu contraseña ha sido restablecida correctamente. Todas las sesiones activas han sido cerradas.'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar la solicitud. Intenta nuevamente.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Validar token de reset sin procesar
     */
    public function validateResetToken(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'token' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos inválidos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $user = Usuario::where('email', $request->email)->first();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuario no encontrado'
                ], 404);
            }

            // Verificar token usando una simulación de reset sin actualizar la contraseña
            $status = Password::broker('usuarios')->reset(
                $request->only('email', 'token') + ['password' => 'temp123', 'password_confirmation' => 'temp123'],
                function ($user, $password) {
                    // No hacer nada, solo validar el token
                    return true;
                }
            );

            if ($status === Password::PASSWORD_RESET) {
                return response()->json([
                    'success' => true,
                    'message' => 'Token válido',
                    'user' => [
                        'email' => $user->email,
                        'name' => $user->name,
                        'role' => $user->role
                    ]
                ]);
            }

            $errorMessage = match($status) {
                Password::INVALID_TOKEN => 'El enlace de recuperación es inválido o ha expirado.',
                Password::INVALID_USER => 'Usuario no encontrado',
                default => 'Token inválido'
            };

            return response()->json([
                'success' => false,
                'message' => $errorMessage
            ], 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al validar el token',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}
