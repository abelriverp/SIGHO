<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * "Sistema" (Facto, Simphony, Opera, GestHotel, WinTB, Avance, ...)
     * anidado bajo una ticket_categoria cuyo requiere_subcategoria = true.
     * `nota` es el recordatorio tipo "un ticket por usuario" que se muestra
     * en el formulario para ese sistema en particular.
     */
    public function up(): void
    {
        Schema::create('ticket_subcategorias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_categoria_id')->constrained('ticket_categorias')->cascadeOnDelete();
            $table->string('label');
            $table->text('nota')->nullable();
            $table->unsignedInteger('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_subcategorias');
    }
};
