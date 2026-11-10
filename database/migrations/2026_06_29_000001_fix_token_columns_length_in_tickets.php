<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // En una instalacion nueva las columnas todavia no existen en este
        // punto: las crea 2026_07_06_000004 ya con longitud 25. Solo se
        // ajustan aqui si ya existen (bases que vienen de versiones viejas).
        Schema::table('tickets', function (Blueprint $table) {
            if (Schema::hasColumn('tickets', 'token_resolucion')) {
                $table->string('token_resolucion', 25)->nullable()->change();
            }
            if (Schema::hasColumn('tickets', 'token_confirmacion')) {
                $table->string('token_confirmacion', 25)->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            if (Schema::hasColumn('tickets', 'token_resolucion')) {
                $table->string('token_resolucion', 20)->nullable()->change();
            }
            if (Schema::hasColumn('tickets', 'token_confirmacion')) {
                $table->string('token_confirmacion', 20)->nullable()->change();
            }
        });
    }
};
