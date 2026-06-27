<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ticket_notas', function (Blueprint $table) {
            $table->text('nota_original')->nullable()->after('nota');
            $table->timestamp('editado_at')->nullable()->after('nota_original');
            $table->unsignedBigInteger('editado_por')->nullable()->after('editado_at');
            $table->foreign('editado_por')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ticket_notas', function (Blueprint $table) {
            $table->dropForeign(['editado_por']);
            $table->dropColumn(['nota_original', 'editado_at', 'editado_por']);
        });
    }
};