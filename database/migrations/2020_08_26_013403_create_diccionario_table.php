<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDiccionarioTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('diccionario', function (Blueprint $table) {
            $table->id();
            $table->string('departamento');
            $table->string('localidad');
            $table->string('municipio');
            $table->string('nombre');
            $table->string('anos_activo');
            $table->string('tipo_persona');
            $table->string('tipo_cargo');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('diccionario');
    }
}
