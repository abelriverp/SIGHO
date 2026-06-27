<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usuario_modulo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('modulo_id')->constrained()->onDelete('cascade');
            $table->timestamp('activado_en')->useCurrent();
            $table->foreignId('activado_por')->nullable()->constrained('users')->onDelete('set null');
            $table->unique(['user_id', 'modulo_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usuario_modulo');
    }
};