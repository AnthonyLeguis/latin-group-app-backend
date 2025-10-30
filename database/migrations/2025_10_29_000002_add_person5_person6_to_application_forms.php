<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Primero eliminar columnas person5 y person6 si existen (de migraciones fallidas anteriores)
        $columns = DB::select("SHOW COLUMNS FROM application_forms");
        $existingColumns = array_column($columns, 'Field');
        
        // Construir lista de columnas a eliminar
        $columnsToDelete = [];
        $person5Columns = ['person5_name', 'person5_relation', 'person5_is_applicant', 'person5_legal_status', 
                          'person5_document_number', 'person5_dob', 'person5_company_name', 'person5_ssn', 
                          'person5_gender', 'person5_wages', 'person5_frequency'];
        $person6Columns = ['person6_name', 'person6_relation', 'person6_is_applicant', 'person6_legal_status', 
                          'person6_document_number', 'person6_dob', 'person6_company_name', 'person6_ssn', 
                          'person6_gender', 'person6_wages', 'person6_frequency'];
        
        foreach (array_merge($person5Columns, $person6Columns) as $column) {
            if (in_array($column, $existingColumns)) {
                $columnsToDelete[] = "DROP COLUMN {$column}";
            }
        }
        
        // Eliminar columnas si existen
        if (!empty($columnsToDelete)) {
            DB::statement("ALTER TABLE application_forms " . implode(", ", $columnsToDelete));
        }
        
        // Convertir tabla a ROW_FORMAT=DYNAMIC para soportar más columnas
        DB::statement('ALTER TABLE application_forms ROW_FORMAT=DYNAMIC');
        
        // Agregar columnas de person5 y person6 en un solo ALTER TABLE con columnas más compactas
        DB::statement("
            ALTER TABLE application_forms
            ADD COLUMN person5_name VARCHAR(80) NULL AFTER person4_frequency,
            ADD COLUMN person5_relation VARCHAR(30) NULL AFTER person5_name,
            ADD COLUMN person5_is_applicant TINYINT(1) DEFAULT 0 AFTER person5_relation,
            ADD COLUMN person5_legal_status VARCHAR(30) NULL AFTER person5_is_applicant,
            ADD COLUMN person5_document_number VARCHAR(30) NULL AFTER person5_legal_status,
            ADD COLUMN person5_dob DATE NULL AFTER person5_document_number,
            ADD COLUMN person5_ssn VARCHAR(20) NULL AFTER person5_dob,
            ADD COLUMN person5_gender CHAR(1) NULL AFTER person5_ssn,
            ADD COLUMN person5_wages DECIMAL(10,2) NULL AFTER person5_gender,
            ADD COLUMN person5_frequency VARCHAR(15) NULL AFTER person5_wages,
            
            ADD COLUMN person6_name VARCHAR(80) NULL AFTER person5_frequency,
            ADD COLUMN person6_relation VARCHAR(30) NULL AFTER person6_name,
            ADD COLUMN person6_is_applicant TINYINT(1) DEFAULT 0 AFTER person6_relation,
            ADD COLUMN person6_legal_status VARCHAR(30) NULL AFTER person6_is_applicant,
            ADD COLUMN person6_document_number VARCHAR(30) NULL AFTER person6_legal_status,
            ADD COLUMN person6_dob DATE NULL AFTER person6_document_number,
            ADD COLUMN person6_ssn VARCHAR(20) NULL AFTER person6_dob,
            ADD COLUMN person6_gender CHAR(1) NULL AFTER person6_ssn,
            ADD COLUMN person6_wages DECIMAL(10,2) NULL AFTER person6_gender,
            ADD COLUMN person6_frequency VARCHAR(15) NULL AFTER person6_wages
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("
            ALTER TABLE application_forms
            DROP COLUMN person5_name,
            DROP COLUMN person5_relation,
            DROP COLUMN person5_is_applicant,
            DROP COLUMN person5_legal_status,
            DROP COLUMN person5_document_number,
            DROP COLUMN person5_dob,
            DROP COLUMN person5_ssn,
            DROP COLUMN person5_gender,
            DROP COLUMN person5_wages,
            DROP COLUMN person5_frequency,
            DROP COLUMN person6_name,
            DROP COLUMN person6_relation,
            DROP COLUMN person6_is_applicant,
            DROP COLUMN person6_legal_status,
            DROP COLUMN person6_document_number,
            DROP COLUMN person6_dob,
            DROP COLUMN person6_ssn,
            DROP COLUMN person6_gender,
            DROP COLUMN person6_wages,
            DROP COLUMN person6_frequency
        ");
    }
};
