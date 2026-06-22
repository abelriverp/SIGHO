<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('assets', function (Blueprint $table) {
            $table->string('division')->nullable();
            $table->string('tipo_equipo')->nullable();
            $table->string('marca')->nullable();
            $table->string('modelo')->nullable();
            $table->string('numero_serie')->nullable();
            $table->string('fecha_adquisicion')->nullable();
            $table->string('factura')->nullable();
            $table->string('procesador')->nullable();
            $table->string('ram')->nullable();
            $table->string('tipo_ram')->nullable();
            $table->string('almacenamiento')->nullable();
            $table->string('sistema_operativo')->nullable();
            $table->string('paqueteria_office')->nullable();
            $table->string('monitor_marca')->nullable();
            $table->string('monitor_modelo')->nullable();
            $table->string('monitor_ns')->nullable();
            $table->string('mouse_marca')->nullable();
            $table->string('mouse_modelo')->nullable();
            $table->string('mouse_ns')->nullable();
            $table->string('teclado_marca')->nullable();
            $table->string('teclado_modelo')->nullable();
            $table->string('teclado_ns')->nullable();
        });
    }
    public function down(): void {}
};