<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->string('status')->default('Abierto')->change(); // Ahora soporta: Abierto, Resuelto, Reabierto, Cerrado
            $table->boolean('confirmado_usuario')->default(false)->after('notas_admin');
            $table->timestamp('fecha_confirmacion')->nullable()->after('confirmado_usuario');
            $table->text('motivo_reapertura')->nullable()->after('fecha_confirmacion');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn(['confirmado_usuario', 'fecha_confirmacion', 'motivo_reapertura']);
        });
    }
};