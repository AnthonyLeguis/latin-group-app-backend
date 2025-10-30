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
            // Token único para confirmación del cliente (64 caracteres)
            $table->string('confirmation_token', 64)->nullable()->unique()->after('pdf_sheet');
            
            // Fecha de expiración del token (3 días desde creación)
            $table->timestamp('token_expires_at')->nullable()->after('confirmation_token');
            
            // Fecha y hora cuando el cliente confirmó (presionó "Aceptar documento")
            $table->timestamp('confirmed_at')->nullable()->after('token_expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('application_forms', function (Blueprint $table) {
            $table->dropColumn(['confirmation_token', 'token_expires_at', 'confirmed_at']);
        });
    }
};
