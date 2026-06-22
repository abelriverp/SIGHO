<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
   
   public function up(): void
{
    Schema::create('assets', function (Blueprint $table) {
        $table->id();
        $table->string('nombre');
        $table->string('nomenclatura')->unique(); 
        $table->string('area');
        $table->string('encargado')->nullable();
        $table->string('ip_address')->nullable();
        $table->string('status')->default('Activo');
        $table->text('notas')->nullable();
        $table->timestamps(); 
    });
}
};
