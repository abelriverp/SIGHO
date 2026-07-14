<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('estancias', function (Blueprint $table) {
            $table->id();
            $table->string('hotel_code', 10);
            $table->foreignId('unidad_id')->constrained('unidades')->onDelete('cascade');
            $table->foreignId('condomino_id')->nullable()->constrained('condominos')->onDelete('set null');

            // propietario | familiar | inquilino_renta | invitado | servicio
            $table->string('tipo_visitante', 30);
            $table->string('nombre_visitante');
            $table->string('identificacion_placas', 100)->nullable();
            $table->unsignedSmallInteger('num_acompanantes')->default(0);

            $table->date('fecha_llegada_prevista');
            $table->date('fecha_salida_prevista')->nullable();
            $table->dateTime('checkin_real')->nullable();
            $table->dateTime('checkout_real')->nullable();

            // reservada | confirmada | checkin | checkout | cancelada | no_show
            $table->string('estado', 20)->default('reservada');

            $table->decimal('tarifa_noche', 10, 2)->nullable();
            $table->text('notas')->nullable();
            $table->foreignId('registrado_por')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index(['hotel_code', 'estado']);
            $table->index(['unidad_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estancias');
    }
};
