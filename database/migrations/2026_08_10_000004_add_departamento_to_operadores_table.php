<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Los operadores dejan de ser una lista privada de "el técnico que los dio
 * de alta" y pasan a ser compartidos por TODO el departamento (ej. Valeria
 * y Gilberto, dos técnicos del mismo departamento, deben ver y usar el
 * mismo catálogo). El campo real de visibilidad ahora es `departamento`
 * (mismo patrón string que ya usan users.departamento y
 * tickets.departamento_destino — ver esas migraciones), no `tecnico_id`.
 *
 * `tecnico_id` NO se elimina: se conserva como "quién lo registró"
 * (auditoría/soporte), pero deja de ser la columna que decide quién puede
 * VER un operador — ver Operador::scopeDeDepartamento() y OperadorPolicy.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('operadores', function (Blueprint $table) {
            $table->string('departamento')->nullable()->after('hotel_code');
        });

        // Backfill: todo operador que ya exista toma el departamento del
        // técnico que lo registró, para no dejar filas huérfanas sin
        // departamento tras el despliegue. Se hace fila por fila con el
        // query builder (no un UPDATE...JOIN crudo) porque ese SQL es
        // exclusivo de MySQL y los tests de este proyecto corren contra
        // SQLite (ver comentario en SlaPoliticaController) — un JOIN en
        // UPDATE rompería la suite al correr las migraciones ahí. El
        // volumen esperado es bajísimo (catálogo chico por propiedad), así
        // que el costo de iterar es irrelevante.
        DB::table('operadores')->orderBy('id')->chunkById(100, function ($operadores) {
            foreach ($operadores as $operador) {
                if (!$operador->tecnico_id) {
                    continue;
                }

                $departamento = DB::table('users')->where('id', $operador->tecnico_id)->value('departamento');

                if ($departamento) {
                    DB::table('operadores')->where('id', $operador->id)->update(['departamento' => $departamento]);
                }
            }
        });

        Schema::table('operadores', function (Blueprint $table) {
            $table->index(['hotel_code', 'departamento']);
        });
    }

    public function down(): void
    {
        Schema::table('operadores', function (Blueprint $table) {
            $table->dropIndex(['hotel_code', 'departamento']);
            $table->dropColumn('departamento');
        });
    }
};
