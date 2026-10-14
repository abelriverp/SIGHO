<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * tickets.hotel_code existía desde antes, pero Ticket::$fillable nunca
     * incluyó 'hotel_code', así que Ticket::create($data) lo descartaba
     * silenciosamente: todos los tickets quedaron con hotel_code NULL.
     * Ya corregimos el fillable (ver app/Models/Ticket.php); aquí solo
     * rellenamos los registros existentes para que no queden invisibles
     * bajo el nuevo filtro por propiedad.
     */
    public function up(): void
    {
        DB::table('tickets')->whereNull('hotel_code')->update([
            'hotel_code' => config('app.hotel_code'),
        ]);
    }

    public function down(): void
    {
        // No reversible de forma segura (no sabemos cuáles eran NULL originalmente).
    }
};
