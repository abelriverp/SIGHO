<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->string('token_resolucion', 25)->nullable()->change();
            $table->string('token_confirmacion', 25)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->string('token_resolucion', 20)->nullable()->change();
            $table->string('token_confirmacion', 20)->nullable()->change();
        });
    }
};