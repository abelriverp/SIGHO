<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activo_plantillas', function (Blueprint $table) {
            $table->id();
            $table->string('hotel_code', 10)->index();
            $table->string('area_nombre', 100);          // "Habitaciones", "Restaurante", "Gym"...
            $table->string('descripcion')->nullable();    // Descripción opcional del área
            // Campos definidos por el admin:
            // [{"nombre":"Modelo","tipo":"text","requerido":true},
            //  {"nombre":"Estado","tipo":"select","opciones":["Bueno","Dañado"],"requerido":true}]
            $table->json('campos')->default('[]');
            $table->boolean('activa')->default(true);
            $table->timestamps();

            // Una sola plantilla por área por hotel
            $table->unique(['hotel_code', 'area_nombre']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activo_plantillas');
    }
};
