<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
 public function up(): void
{
    Schema::create('habitacion_activos', function (Blueprint $table) {
        $table->id();
        $table->string('numero_habitacion')->unique();
        $table->integer('piso');

        // INFRAESTRUCTURA DE RED (APs)
        $table->string('ap_modelo')->nullable(); 
        $table->string('ap_serie')->nullable();  
        $table->string('ap_mac')->nullable();    
        $table->string('ap_ip')->nullable();     

        // ENTRETENIMIENTO (TVs y Controles)
        $table->string('tv_marca')->nullable();  
        $table->string('tv_modelo')->nullable(); 
        $table->string('tv_serie')->nullable();  
        $table->string('tv_pulgadas')->nullable(); 
        $table->enum('control_remoto_estado', ['Funciona', 'Dañado', 'Extraviado'])->default('Funciona');
        $table->string('control_serie')->nullable(); // <-- NUEVO CAMPO: Serie del control

        // SEGURIDAD Y OTROS
        $table->string('caja_fuerte_modelo')->nullable();
        $table->string('caja_fuerte_serie')->nullable();
        $table->string('caja_fuerte_bateria')->default('OK'); 

        $table->text('notas_tecnicas')->nullable(); 
        $table->timestamps();
    });
}
};
