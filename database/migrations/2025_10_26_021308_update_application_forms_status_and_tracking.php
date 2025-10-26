<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('application_forms', function (Blueprint $table) {
            // Solo agregar si no existen
            if (!Schema::hasColumn('application_forms', 'reviewed_by')) {
                $table->foreignId('reviewed_by')->nullable()->after('status_comment')->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('application_forms', 'reviewed_at')) {
                $table->timestamp('reviewed_at')->nullable()->after('status_comment');
            }
        });

        // Actualizar valores existentes de status a 'pendiente' si están vacíos
        DB::table('application_forms')
            ->whereNull('status')
            ->orWhere('status', '')
            ->update(['status' => 'pendiente']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('application_forms', function (Blueprint $table) {
            $table->dropForeign(['reviewed_by']);
            $table->dropIndex(['reviewed_by']);
            $table->dropColumn(['reviewed_by', 'reviewed_at']);
        });
    }
};
