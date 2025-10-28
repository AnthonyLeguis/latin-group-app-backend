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
        // Primero actualizar valores existentes para que coincidan con los nuevos
        DB::statement("UPDATE application_forms SET status = 'activo' WHERE status = 'Activo'");
        DB::statement("UPDATE application_forms SET status = 'inactivo' WHERE status = 'Inactivo'");
        DB::statement("UPDATE application_forms SET status = 'pendiente' WHERE status = 'En Revisión'");
        
        // Modificar la columna ENUM con los nuevos valores
        DB::statement("ALTER TABLE application_forms MODIFY COLUMN status ENUM('pendiente', 'activo', 'inactivo', 'rechazado') DEFAULT 'pendiente'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revertir a los valores originales
        DB::statement("UPDATE application_forms SET status = 'Activo' WHERE status = 'activo'");
        DB::statement("UPDATE application_forms SET status = 'Inactivo' WHERE status = 'inactivo'");
        DB::statement("UPDATE application_forms SET status = 'En Revisión' WHERE status = 'pendiente'");
        
        // Restaurar la columna ENUM con los valores originales
        DB::statement("ALTER TABLE application_forms MODIFY COLUMN status ENUM('Activo', 'Inactivo', 'En Revisión') DEFAULT 'En Revisión'");
    }
};
