<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('hoteles', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->string('hotel_code')->unique(); // Ejemplo: HEMZT, MAZ, etc.
            $blueprint->string('nombre');       // Ejemplo: Hotel Emporio Mazatlán
            $blueprint->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hoteles');
    }
};