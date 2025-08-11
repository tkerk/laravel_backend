<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use App\Models\Usuario;

class TestEmail extends Command
{
    protected $signature = 'email:test {email}';
    protected $description = 'Enviar un email de prueba';

    public function handle()
    {
        $email = $this->argument('email');
        
        try {
            Mail::raw('¡Hola! Este es un email de prueba desde WithDomine. Si recibes este mensaje, la configuración de Gmail está funcionando correctamente.', function ($message) use ($email) {
                $message->to($email)
                        ->subject('Prueba de Email - WithDomine')
                        ->from(config('mail.from.address'), config('mail.from.name'));
            });
            
            $this->info("✅ Email enviado exitosamente a: {$email}");
        } catch (\Exception $e) {
            $this->error("❌ Error al enviar email: " . $e->getMessage());
        }
    }
}
