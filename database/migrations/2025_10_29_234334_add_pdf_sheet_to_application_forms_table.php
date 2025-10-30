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
            // Agregar campo para almacenar la ruta del PDF generado
            // Ruta relativa desde storage/app/public (ej: 'pdf_sheets/123/form_456.pdf')
            $table->string('pdf_sheet')->nullable()->after('pending_changes_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('application_forms', function (Blueprint $table) {
            $table->dropColumn('pdf_sheet');
        });
    }
};
