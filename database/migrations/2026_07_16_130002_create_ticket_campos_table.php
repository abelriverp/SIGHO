<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Definición de un campo dinámico del formulario de ticket. Cuelga de
     * UNA de dos posibles (nunca ambas, nunca ninguna — validado en
     * TicketCampo::boot() y en el controlador):
     *   - ticket_subcategoria_id: cuando su categoría exige elegir sistema.
     *   - ticket_categoria_id:    cuando la categoría no tiene subcategorías
     *                              (ej. "Otro" -> un solo campo de texto).
     *
     * `name` es la llave técnica (usada como campos[name] en el form y en
     * campos_extra->campos al guardar el ticket); debe ser único dentro de
     * su categoría/subcategoría, no globalmente.
     */
    public function up(): void
    {
        Schema::create('ticket_campos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_categoria_id')->nullable()->constrained('ticket_categorias')->cascadeOnDelete();
            $table->foreignId('ticket_subcategoria_id')->nullable()->constrained('ticket_subcategorias')->cascadeOnDelete();
            $table->string('name');
            $table->string('label');
            $table->enum('tipo_campo', ['text', 'textarea', 'email'])->default('text');
            $table->boolean('requerido')->default(true);
            $table->unsignedInteger('orden')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_campos');
    }
};
