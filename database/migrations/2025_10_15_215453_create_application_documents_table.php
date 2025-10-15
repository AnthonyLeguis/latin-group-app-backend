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
        Schema::create('application_documents', function (Blueprint $table) {
            $table->id();

            // Foreign Keys
            $table->foreignId('application_form_id')->constrained('application_forms')->onDelete('cascade');
            $table->foreignId('uploaded_by')->constrained('users')->onDelete('cascade');

            // File Information
            $table->string('original_name'); // Nombre original del archivo
            $table->string('file_name'); // Nombre en el servidor
            $table->string('file_path'); // Ruta completa del archivo
            $table->string('mime_type'); // Tipo MIME del archivo
            $table->integer('file_size'); // Tamaño en bytes
            $table->string('document_type')->nullable(); // Tipo de documento (cedula, recibo, etc.)

            $table->timestamps();

            // Indexes
            $table->index(['application_form_id', 'document_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('application_documents');
    }
};
