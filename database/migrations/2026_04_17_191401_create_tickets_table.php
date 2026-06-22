<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Quien lo reporta
            $table->string('area');
            $table->text('descripcion');
            $table->string('prioridad')->default('Media'); // Baja, Media, Alta
            $table->enum('status', ['Abierto', 'Resuelto'])->default('Abierto');
            $table->string('archivo_path')->nullable(); // Foto o video de la falla
            $table->text('notas_admin')->nullable(); // Notas de cómo se resolvió
            $table->timestamp('fecha_resolucion')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};