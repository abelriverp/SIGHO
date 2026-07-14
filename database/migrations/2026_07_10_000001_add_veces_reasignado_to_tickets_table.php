<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            // Cuántas veces se ha reasignado este ticket a otro departamento.
            // Sirve para limitar el número de reasignaciones (evitar que un
            // ticket rebote entre departamentos sin que nadie lo resuelva).
            $table->unsignedTinyInteger('veces_reasignado')->default(0)->after('departamento_destino');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn('veces_reasignado');
        });
    }
};
