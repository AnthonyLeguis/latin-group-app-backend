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
        Schema::table('users', function (Blueprint $table) {
            // Total de minutos acumulados que el agente ha estado activo
            $table->integer('total_active_time')->default(0)->comment('Total active time in minutes');
            
            // Timestamp del inicio de la sesión actual (null si no está en sesión)
            $table->timestamp('current_session_start')->nullable()->comment('Current session start timestamp');
            
            // Duración de la última sesión en minutos
            $table->integer('last_session_duration')->nullable()->comment('Last session duration in minutes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['total_active_time', 'current_session_start', 'last_session_duration']);
        });
    }
};
