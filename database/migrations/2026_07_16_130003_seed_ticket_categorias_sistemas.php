<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Migra el catálogo de Sistemas (Avance/Facto/Simphony/Opera/GestHotel/
     * WinTB/Otro), que hasta ahora vivía hardcodeado en
     * config/tickets_sistemas.php, a filas reales de ticket_categorias/
     * ticket_subcategorias/ticket_campos — una copia por cada propiedad ya
     * dada de alta en `hoteles`, para que ninguna deje de ver lo que ya
     * tenía funcionando y, de ahí en adelante, cada quien lo administre
     * desde el panel (admin/gerente) sin tocar código.
     *
     * config/tickets_sistemas.php se queda en el repo solo como el dato
     * semilla de esta migración (y como referencia/plantilla para dar de
     * alta una propiedad nueva) — ya no lo lee ni el formulario ni el
     * controlador, que a partir de este cambio consultan la base de datos.
     */
    public function up(): void
    {
        $departamentoId = DB::table('departamentos')->where('nombre', 'Sistemas')->value('id');

        if (!$departamentoId) {
            return; // Entorno sin el departamento "Sistemas": nada que sembrar.
        }

        $hotelCodes = DB::table('hoteles')->pluck('hotel_code');

        if ($hotelCodes->isEmpty()) {
            return;
        }

        $tipos = config('tickets_sistemas.tipos', []);
        $ahora = now();

        foreach ($hotelCodes as $hotelCode) {
            $ordenCategoria = 0;

            foreach ($tipos as $tipo) {
                $categoriaId = DB::table('ticket_categorias')->insertGetId([
                    'hotel_code'             => $hotelCode,
                    'departamento_id'        => $departamentoId,
                    'label'                  => $tipo['label'],
                    'requiere_subcategoria'  => !empty($tipo['sistemas']),
                    'orden'                  => $ordenCategoria++,
                    'activo'                 => true,
                    'created_at'             => $ahora,
                    'updated_at'             => $ahora,
                ]);

                if (!empty($tipo['sistemas'])) {
                    $ordenSistema = 0;
                    foreach ($tipo['sistemas'] as $sistema) {
                        $subcategoriaId = DB::table('ticket_subcategorias')->insertGetId([
                            'ticket_categoria_id' => $categoriaId,
                            'label'               => $sistema['label'],
                            'nota'                => $sistema['nota'] ?? null,
                            'orden'               => $ordenSistema++,
                            'activo'              => true,
                            'created_at'          => $ahora,
                            'updated_at'          => $ahora,
                        ]);

                        $this->insertarCampos($sistema['campos'] ?? [], null, $subcategoriaId, $ahora);
                    }
                } else {
                    $this->insertarCampos($tipo['campos'] ?? [], $categoriaId, null, $ahora);
                }
            }
        }
    }

    private function insertarCampos(array $campos, ?int $categoriaId, ?int $subcategoriaId, $ahora): void
    {
        $orden = 0;
        foreach ($campos as $campo) {
            DB::table('ticket_campos')->insert([
                'ticket_categoria_id'    => $categoriaId,
                'ticket_subcategoria_id' => $subcategoriaId,
                'name'                   => $campo['name'],
                'label'                  => $campo['label'],
                'tipo_campo'             => $campo['type'] ?? 'text',
                'requerido'              => $campo['required'] ?? true,
                'orden'                  => $orden++,
                'created_at'             => $ahora,
                'updated_at'             => $ahora,
            ]);
        }
    }

    public function down(): void
    {
        // No se puede distinguir con certeza "lo sembrado aquí" de ediciones
        // manuales que el admin haya hecho después desde el panel, así que
        // el rollback deliberadamente no borra nada. Si de verdad se
        // necesita vaciar el catálogo, hazlo desde el panel o truncando las
        // 3 tablas a mano.
    }
};
