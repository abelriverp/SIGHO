<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->json('fotos_resolucion')->nullable()->after('archivo_path');
            // Tokens de auditoría (si aún no existen)
            $table->string('token_resolucion', 20)->nullable()->after('fotos_resolucion');
            $table->string('token_confirmacion', 20)->nullable()->after('token_resolucion');
            $table->string('ip_resolucion', 45)->nullable()->after('token_confirmacion');
            $table->string('ip_confirmacion', 45)->nullable()->after('ip_resolucion');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn([
                'fotos_resolucion',
                'token_resolucion',
                'token_confirmacion',
                'ip_resolucion',
                'ip_confirmacion',
            ]);
        });
    }
};