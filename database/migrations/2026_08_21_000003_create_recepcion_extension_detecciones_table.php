<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Encabezado de cada comparación de "extensión de estancia" (un PDF de
 * salidas contra un PDF de llegadas). Port a módulo web del detector de
 * extensiones que antes vivía como app de escritorio independiente — ver
 * App\Services\Recepcion\ExtensionDetectorService y App\Models\ExtensionDeteccion.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recepcion_extension_detecciones', function (Blueprint $table) {
            $table->id();
            $table->string('hotel_code');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('archivo_salidas');
            $table->string('archivo_llegadas');
            $table->unsignedInteger('total_coincidencias')->default(0);
            $table->timestamps();

            $table->index('hotel_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recepcion_extension_detecciones');
    }
};
