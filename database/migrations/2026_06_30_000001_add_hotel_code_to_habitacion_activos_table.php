<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('habitacion_activos', function (Blueprint $table) {
            $table->string('hotel_code', 10)->default(config('app.hotel_code'))->after('id');
            $table->index('hotel_code');
        });
    }

    public function down(): void
    {
        Schema::table('habitacion_activos', function (Blueprint $table) {
            $table->dropIndex(['hotel_code']);
            $table->dropColumn('hotel_code');
        });
    }
};
