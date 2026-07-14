<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Esta migración quedó fechada 2025-01-01, ANTES de
        // 2026_04_17_191401_create_tickets_table.php. En tu base real nunca
        // se nota (ya corrió hace tiempo y Laravel no la repite), pero en una
        // instalación nueva (o en las pruebas automatizadas, que arman la
        // base desde cero) truena porque la tabla 'tickets' todavía no
        // existe en este punto. La agregamos de forma correcta y sin
        // duplicar columnas en 2026_07_06_000004_add_fotos_resolucion_to_tickets_table_fix.php.
        if (! Schema::hasTable('tickets')) {
            return;
        }

        Schema::table('tickets', function (Blueprint $table) {
            if (! Schema::hasColumn('tickets', 'fotos_resolucion')) {
                $table->json('fotos_resolucion')->nullable()->after('archivo_path');
            }
            if (! Schema::hasColumn('tickets', 'token_resolucion')) {
                $table->string('token_resolucion', 20)->nullable()->after('fotos_resolucion');
            }
            if (! Schema::hasColumn('tickets', 'token_confirmacion')) {
                $table->string('token_confirmacion', 20)->nullable()->after('token_resolucion');
            }
            if (! Schema::hasColumn('tickets', 'ip_resolucion')) {
                $table->string('ip_resolucion', 45)->nullable()->after('token_confirmacion');
            }
            if (! Schema::hasColumn('tickets', 'ip_confirmacion')) {
                $table->string('ip_confirmacion', 45)->nullable()->after('ip_resolucion');
            }
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