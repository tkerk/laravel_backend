<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('notificaciones', function (Blueprint $table) {
            $table->id();
            $table->string('id_huesped')->nullable(); // Para notificaciones de huéspedes
            $table->string('titulo');
            $table->text('mensaje');
            $table->enum('tipo', ['huesped', 'admin'])->default('huesped');
            $table->boolean('leida')->default(false);
            $table->unsignedBigInteger('multa_id')->nullable();
            $table->timestamps();

            
            $table->index(['id_huesped', 'leida']);
            $table->index(['tipo', 'leida']);
            
            
            $table->foreign('multa_id')->references('id')->on('multas')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notificaciones');
    }
};
