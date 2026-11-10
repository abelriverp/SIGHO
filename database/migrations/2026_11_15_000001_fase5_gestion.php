<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fase 5 (nov 2026): gestion de problemas y cambios, login con Active
 * Directory y tokens para la API.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('problemas', function (Blueprint $table) {
            $table->id();
            $table->string('hotel_code', 10)->index();
            $table->string('titulo', 200);
            $table->text('descripcion');
            $table->string('estado', 20)->default('abierto'); // abierto | en_analisis | causa_identificada | resuelto | cerrado
            $table->string('prioridad', 10)->default('Media');
            $table->text('workaround')->nullable();
            $table->text('causa_raiz')->nullable();
            $table->text('solucion_definitiva')->nullable();
            $table->foreignId('asset_id')->nullable()->constrained('assets')->nullOnDelete();
            $table->foreignId('ubicacion_id')->nullable()->constrained('ubicaciones')->nullOnDelete();
            $table->foreignId('responsable_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('creado_por')->constrained('users')->cascadeOnDelete();
            $table->timestamp('resuelto_at')->nullable();
            $table->timestamps();
            $table->index(['hotel_code', 'estado']);
        });

        Schema::create('problema_ticket', function (Blueprint $table) {
            $table->foreignId('problema_id')->constrained('problemas')->cascadeOnDelete();
            $table->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete();
            $table->primary(['problema_id', 'ticket_id']);
        });

        Schema::create('cambios', function (Blueprint $table) {
            $table->id();
            $table->string('hotel_code', 10)->index();
            $table->string('titulo', 200);
            $table->text('descripcion');
            $table->string('tipo', 15)->default('normal');      // normal | estandar | emergencia
            $table->string('riesgo', 10)->default('Media');     // Baja | Media | Alta
            $table->text('impacto')->nullable();                // que se afecta y a quien se avisa
            $table->text('plan_implementacion')->nullable();
            $table->text('plan_rollback')->nullable();
            $table->dateTime('ventana_inicio')->nullable();
            $table->dateTime('ventana_fin')->nullable();
            $table->string('estado', 25)->default('borrador');
            $table->foreignId('problema_id')->nullable()->constrained('problemas')->nullOnDelete();
            $table->foreignId('solicitado_por')->constrained('users')->cascadeOnDelete();
            $table->foreignId('aprobado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('aprobado_at')->nullable();
            $table->text('comentario_aprobacion')->nullable();
            $table->text('resultado')->nullable();
            $table->timestamp('completado_at')->nullable();
            $table->timestamps();
            $table->index(['hotel_code', 'estado']);
            $table->index(['hotel_code', 'ventana_inicio']);
        });

        Schema::create('asset_cambio', function (Blueprint $table) {
            $table->foreignId('cambio_id')->constrained('cambios')->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            $table->primary(['cambio_id', 'asset_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('usuario_ad', 100)->nullable()->after('email');
        });

        Schema::create('api_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('hotel_code', 10)->nullable()->index();
            $table->string('nombre', 100);
            $table->string('token_hash', 64)->unique();
            $table->string('prefijo', 8);                       // primeros caracteres, para identificarlo en pantalla
            $table->boolean('puede_escribir')->default(false);
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // a nombre de quién se crean los tickets
            $table->timestamp('ultimo_uso_at')->nullable();
            $table->timestamp('expira_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_tokens');
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn('usuario_ad'));
        Schema::dropIfExists('asset_cambio');
        Schema::dropIfExists('cambios');
        Schema::dropIfExists('problema_ticket');
        Schema::dropIfExists('problemas');
    }
};
