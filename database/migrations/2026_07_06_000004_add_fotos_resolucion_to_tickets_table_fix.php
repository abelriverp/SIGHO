<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Réplica, con fecha correcta, de 2025_01_01_000001_add_fotos_resolucion_to_tickets_table.php
 * (esa migración quedó fechada antes de crear la tabla 'tickets', así que en
 * una instalación nueva nunca llegaba a agregar estas columnas). Esta versión
 * corre después de que la tabla existe y cada columna se agrega solo si
 * todavía no está — así es segura tanto en instalaciones nuevas como en tu
 * base real, donde estas columnas ya existen.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            if (! Schema::hasColumn('tickets', 'fotos_resolucion')) {
                $table->json('fotos_resolucion')->nullable()->after('archivo_path');
            }
            if (! Schema::hasColumn('tickets', 'token_resolucion')) {
                $table->string('token_resolucion', 20)->nullable();
            }
            if (! Schema::hasColumn('tickets', 'token_confirmacion')) {
                $table->string('token_confirmacion', 20)->nullable();
            }
            if (! Schema::hasColumn('tickets', 'ip_resolucion')) {
                $table->string('ip_resolucion', 45)->nullable();
            }
            if (! Schema::hasColumn('tickets', 'ip_confirmacion')) {
                $table->string('ip_confirmacion', 45)->nullable();
            }
        });
    }

    public function down(): void
    {
        // No-op: el down() original (2025_01_01...) ya cubre el rollback
        // cuando corresponde; aquí no queremos arriesgarnos a tronar si
        // las columnas fueron creadas por la migración vieja.
    }
};
