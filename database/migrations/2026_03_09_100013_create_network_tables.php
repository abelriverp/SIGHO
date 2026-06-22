<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        
        Schema::create('network_devices', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('tipo');   
            $table->integer('total_ports')->default(24); 
            $table->string('ubicacion')->nullable();
            $table->timestamps();
        });

        Schema::create('network_ports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('network_device_id')->constrained()->onDelete('cascade');
            $table->integer('port_number'); 
            $table->string('connected_to')->nullable(); 
            $table->string('status')->default('Libre');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('network_ports');
        Schema::dropIfExists('network_devices');
    }
};