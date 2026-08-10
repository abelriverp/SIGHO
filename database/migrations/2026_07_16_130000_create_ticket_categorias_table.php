<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Catálogo de "tipo de solicitud" para tickets, editable desde el panel
     * (admin y gerente) en vez de vivir hardcodeado en config/tickets_sistemas.php.
     *
     * Generalizado desde el primer momento: no es exclusivo de "Sistemas"
     * (departamento_id apunta a cualquier fila de `departamentos`), para que
     * a futuro cualquier área pueda definir sus propias categorías dinámicas
     * sin rediseñar esto.
     *
     * Por propiedad: cada hotel_code administra su propio catálogo (a
     * diferencia de `departamentos`/`areas`, que son globales), porque no
     * todas las propiedades usan los mismos sistemas/software.
     *
     * requiere_subcategoria distingue dos formas:
     *  - true  -> el usuario elige además un "sistema" (ver ticket_subcategorias)
     *  - false -> va directo a los campos de esta misma categoría (ej. "Otro")
     */
    public function up(): void
    {
        Schema::create('ticket_categorias', function (Blueprint $table) {
            $table->id();
            $table->string('hotel_code');
            $table->foreignId('departamento_id')->constrained('departamentos')->cascadeOnDelete();
            $table->string('label');
            $table->boolean('requiere_subcategoria')->default(true);
            $table->unsignedInteger('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index(['hotel_code', 'departamento_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_categorias');
    }
};
