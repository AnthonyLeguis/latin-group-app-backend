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
            // Campo para rastrear si fue un admin quien creó el registro
            $table->foreignId('created_by_admin')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            $table->index('created_by_admin', 'users_created_by_admin_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['created_by_admin']);
            $table->dropIndex('users_created_by_admin_index');
            $table->dropColumn('created_by_admin');
        });
    }
};
