<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hoteles', function (Blueprint $table) {
            // 'hotel' | 'condominio' | 'mixto'
            $table->string('tipo', 20)->default('hotel')->after('nombre');
        });
    }

    public function down(): void
    {
        Schema::table('hoteles', function (Blueprint $table) {
            $table->dropColumn('tipo');
        });
    }
};
