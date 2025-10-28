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
        Schema::table('application_forms', function (Blueprint $table) {
            // Campos para rastrear cambios pendientes de aprobación
            $table->json('pending_changes')->nullable()->after('reviewed_at'); // Cambios pendientes en formato JSON
            $table->boolean('has_pending_changes')->default(false)->after('pending_changes'); // Flag para indicar si hay cambios pendientes
            $table->timestamp('pending_changes_at')->nullable()->after('has_pending_changes'); // Fecha de los cambios pendientes
            $table->unsignedBigInteger('pending_changes_by')->nullable()->after('pending_changes_at'); // ID del agente que hizo los cambios
            
            // Foreign key para el agente que hizo los cambios
            $table->foreign('pending_changes_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('application_forms', function (Blueprint $table) {
            $table->dropForeign(['pending_changes_by']);
            $table->dropColumn(['pending_changes', 'has_pending_changes', 'pending_changes_at', 'pending_changes_by']);
        });
    }
};
