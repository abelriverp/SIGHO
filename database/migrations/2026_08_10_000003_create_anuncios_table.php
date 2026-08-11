<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Anuncios de "nueva función/cambio" que un admin redacta y envía por
     * correo a los usuarios con acceso al módulo afectado (ver
     * App\Models\Anuncio::destinatarios() y AnuncioController). Queda
     * guardado aquí como historial para la pantalla "Novedades", que
     * cualquier usuario puede repasar después aunque haya perdido el
     * correo.
     *
     * A propósito SIN hotel_code: por ahora un anuncio le llega a
     * cualquier usuario con el módulo, sin importar su propiedad — mismo
     * criterio que ya usan las notificaciones de tickets (admin/técnicos
     * de cualquier hotel). Si más adelante se necesita filtrar por
     * propiedad, se agrega la columna entonces.
     */
    public function up(): void
    {
        Schema::create('anuncios', function (Blueprint $table) {
            $table->id();
            // Nullable = "para todos los usuarios" (broadcast general), en
            // vez de un módulo específico.
            $table->foreignId('modulo_id')->nullable()->constrained('modulos')->nullOnDelete();
            $table->string('titulo');
            $table->text('mensaje');
            // Nullable para no perder el historial si la cuenta del admin
            // que lo envió se elimina después.
            $table->foreignId('enviado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('total_destinatarios')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('anuncios');
    }
};
