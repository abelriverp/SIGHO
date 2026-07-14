<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unidades', function (Blueprint $table) {
            $table->id();
            $table->string('hotel_code', 10);
            $table->string('identificador', 20); // "101", "PH-2", "Depto 4B"...
            $table->string('torre', 50)->nullable();
            $table->string('piso', 10)->nullable();
            $table->string('tipo_unidad', 50)->nullable(); // studio, 1BR, 2BR, penthouse...
            $table->foreignId('condomino_id')->nullable()->constrained('condominos')->onDelete('set null');
            $table->string('estatus', 20)->default('Vacia'); // Vacia | Ocupada | Rentada | Mantenimiento
            $table->text('notas')->nullable();
            $table->timestamps();

            $table->unique(['hotel_code', 'identificador']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unidades');
    }
};
