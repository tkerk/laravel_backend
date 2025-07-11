<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   
    public function up(): void
    {
        Schema::create('multas', function (Blueprint $table) {
            $table->id();
            $table->decimal('cantidad', 10, 2);
            $table->string('razon');
            $table->timestamp('emitido_a_las')->nullable();
            $table->string('estatus')->default('pendiente');
            $table->unsignedBigInteger('id_huesped');
            $table->timestamp('notificado_en')->nullable();
            $table->boolean('visualizado')->default(false);
            $table->timestamps();

            $table->foreign('id_huesped')->references('id_huesped')->on('usuarios')->onDelete('cascade');
        });
    }

   
    public function down(): void
    {
        Schema::dropIfExists('multas');
    }
};