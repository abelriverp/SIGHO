<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
    public function up(): void
    {
        Schema::create('revisions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('asset_id')->constrained('assets')->onDelete('cascade'); 
            
            $table->string('problema');
            $table->text('descripcion')->nullable();
            $table->text('revision')->nullable();     
            $table->text('diagnostico')->nullable();  
            $table->text('solucion')->nullable();     
            $table->string('resultado')->nullable(); 
            $table->timestamps();
        });
    }

    
    public function down(): void
    {
        Schema::dropIfExists('revisions');
    }
};