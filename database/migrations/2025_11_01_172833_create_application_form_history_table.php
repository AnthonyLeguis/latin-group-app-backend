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
        Schema::create('application_form_history', function (Blueprint $table) {
            $table->id();
            
            // Relación con la planilla
            $table->foreignId('application_form_id')->constrained('application_forms')->onDelete('cascade');
            
            // Tipo de acción realizada
            $table->enum('action', [
                'status_changed',           // Cambio de estado (pendiente -> activo, etc.)
                'pending_changes_proposed', // Agente propone cambios
                'pending_changes_approved', // Admin aprueba cambios
                'pending_changes_rejected', // Admin rechaza cambios
                'created',                  // Planilla creada
                'updated'                   // Actualización general
            ]);
            
            // Usuario que realizó la acción
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            
            // Comentario/razón de la acción
            $table->text('comment')->nullable();
            
            // Datos adicionales en JSON (opcional, para metadata)
            $table->json('metadata')->nullable();
            
            // Estado anterior y nuevo (para cambios de estado)
            $table->string('old_status')->nullable();
            $table->string('new_status')->nullable();
            
            // Timestamp
            $table->timestamp('created_at')->useCurrent();
            
            // Indexes para búsquedas eficientes
            $table->index(['application_form_id', 'created_at']);
            $table->index('action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('application_form_history');
    }
};
