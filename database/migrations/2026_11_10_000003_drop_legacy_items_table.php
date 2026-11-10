<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Fase 0 (nov 2026): la tabla `items` (y el modelo App\Models\Item) quedaron
 * sin uso desde que existe Inventario de Suministros (`inventario_items`).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('items');
    }

    public function down(): void
    {
        // Sin vuelta atras: tabla legacy sin uso.
    }
};
