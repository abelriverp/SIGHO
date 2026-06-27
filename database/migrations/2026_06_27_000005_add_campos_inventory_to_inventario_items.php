<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventario_items', function (Blueprint $table) {
            $table->string('imagen_path')->nullable()->after('descripcion');
            $table->string('pdf_path')->nullable()->after('imagen_path');
            $table->string('status')->default('Disponible')->after('pdf_path');
            $table->decimal('cantidad', 10, 2)->default(0)->after('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('inventario_items', function (Blueprint $table) {
            $table->dropColumn(['imagen_path', 'pdf_path', 'status', 'cantidad']);
        });
    }
};