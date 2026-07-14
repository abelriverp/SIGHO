<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * archivo_path (columna vieja) solo guardaba UN archivo de evidencia.
     * Esta columna nueva guarda varios (arreglo JSON), igual que ya se
     * hace con fotos_resolucion. No se toca ni se borra archivo_path
     * para no perder evidencia de tickets ya existentes — el show y el
     * index siguen mostrando esa columna vieja como respaldo cuando un
     * ticket no tiene archivos_evidencia.
     */
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->json('archivos_evidencia')->nullable()->after('archivo_path');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn('archivos_evidencia');
        });
    }
};
