<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Agrega la columna de control con valor true por defecto
            $table->boolean('must_change_password')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Si hacemos un rollback, elimina la columna limpia
            $table->dropColumn('must_change_password');
        });
    }
};