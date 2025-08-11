<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSesionesTable extends Migration
{
    public function up()
    {
        Schema::create('sesiones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('usuario_id');
            $table->unsignedBigInteger('token_id');
            $table->text('token');
            $table->timestamps();

            $table->foreign('usuario_id')->references('id_huesped')->on('usuarios')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('sesiones');
    }
}
