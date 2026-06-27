<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('modulos', function (Blueprint $table) {
            $table->id();
            $table->string('clave')->unique();        // ej: 'tickets', 'inventario'
            $table->string('nombre');                  // ej: 'Gestión de Tickets'
            $table->string('descripcion')->nullable();
            $table->string('icono')->default('cube'); // nombre icono Heroicon
            $table->string('ruta_base')->nullable();  // ej: 'tickets.index'
            $table->boolean('activo')->default(true); // para desactivar globalmente
            $table->integer('orden')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('modulos');
    }
};