<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventario_movimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventario_item_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // quien registró
            $table->enum('tipo', ['entrada', 'salida']);
            $table->decimal('cantidad', 10, 2);
            $table->decimal('cantidad_anterior', 10, 2); // snapshot antes del movimiento
            $table->decimal('cantidad_nueva', 10, 2);    // snapshot después
            $table->string('motivo')->nullable();         // "Reabastecimiento", "Consumo diario", etc.
            $table->text('notas')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventario_movimientos');
    }
};