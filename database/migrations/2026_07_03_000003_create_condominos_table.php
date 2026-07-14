<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('condominos', function (Blueprint $table) {
            $table->id();
            $table->string('hotel_code', 10);
            $table->string('nombre');
            $table->string('telefono', 30)->nullable();
            $table->string('email')->nullable();
            $table->string('identificacion', 100)->nullable(); // INE, pasaporte, etc.
            $table->date('fecha_alta')->nullable();
            $table->text('notas')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null'); // quién lo registró
            $table->timestamps();

            $table->index(['hotel_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('condominos');
    }
};
