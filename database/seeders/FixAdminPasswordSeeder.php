<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Usuario;
use Illuminate\Support\Facades\Hash;

class FixAdminPasswordSeeder extends Seeder
{
    public function run()
    {
        echo "🔧 Corrigiendo contraseña del admin...\n";
        
        // Buscar o crear admin
        $admin = Usuario::updateOrCreate(
            ['email' => 'admin@test.com'],
            [
                'name' => 'Administrador',
                'email' => 'admin@test.com',
                'password' => Hash::make('123456789'),
                'role' => 'admin'
            ]
        );
        
        echo "✅ Admin configurado correctamente:\n";
        echo "📧 Email: admin@test.com\n";
        echo "🔑 Password: 123456789\n";
        echo "🔒 Hash: " . substr($admin->password, 0, 20) . "...\n";
        
        // Verificar que funcione
        if (Hash::check('123456789', $admin->password)) {
            echo "✅ Verificación exitosa!\n";
        } else {
            echo "❌ Error en la verificación\n";
        }
    }
}
