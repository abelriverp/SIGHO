<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catálogo de "operadores": personas que ejecutan trabajo en campo pero no
 * tienen cuenta en el sistema (no inician sesión, no tienen rol ni
 * permisos). Cada técnico mantiene su propia lista, para poder dejar
 * constancia de quién hizo el trabajo al resolver un ticket y, con eso,
 * medir cuántos tickets sacó cada operador (ej. "cuántos hizo Juan").
 *
 * Es un catálogo, no texto libre en el ticket, para que el conteo por
 * nombre no se rompa por variaciones de escritura ("Juan" vs "juan perez").
 *
 * activo en vez de borrado físico: si un operador se da de baja, sus
 * tickets ya resueltos deben seguir contando en las métricas históricas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operadores', function (Blueprint $table) {
            $table->id();
            $table->string('hotel_code');
            $table->foreignId('tecnico_id')->constrained('users')->cascadeOnDelete();
            $table->string('nombre');
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index('hotel_code');
            $table->index(['tecnico_id', 'activo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operadores');
    }
};
