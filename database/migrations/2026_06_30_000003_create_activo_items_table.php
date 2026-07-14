<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activo_items', function (Blueprint $table) {
            $table->id();
            $table->string('hotel_code', 10)->index();
            $table->foreignId('plantilla_id')->constrained('activo_plantillas')->cascadeOnDelete();
            $table->string('area_nombre', 100);          // Desnormalizado para consultas rápidas
            $table->string('ubicacion', 150);            // "Hab. 101", "Mesa 5", "Lobby", "Caminadora 3"
            $table->string('nombre_item', 150);          // "Access Point", "TV", "Cafetera"
            // Valores capturados según la plantilla:
            // {"Modelo": "UAP-AC-Pro", "Número de Serie": "NS-001", "MAC": "AA:BB:CC"}
            $table->json('valores')->default('{}');
            $table->text('notas')->nullable();
            $table->timestamps();

            $table->index(['hotel_code', 'area_nombre']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activo_items');
    }
};
