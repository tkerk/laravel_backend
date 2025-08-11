<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
    public function up(): void
    {
        Schema::create('usuarios', function (Blueprint $table) {
            $table->bigIncrements('id_huesped');
            $table->string('name');
            $table->string('email')->unique();
            $table->string('passwordd');
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            $table->string('role')->default('user'); // Agrega esto
            $table->timestamps();
        });
    }

    
    public function down(): void
    {
        Schema::dropIfExists('usuarios');
    }
};