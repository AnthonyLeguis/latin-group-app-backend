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
        Schema::create('application_forms', function (Blueprint $table) {
            $table->id();

            // Foreign Keys
            $table->foreignId('client_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('agent_id')->constrained('users')->onDelete('cascade');

            // Application Data (1-24)
            $table->string('agent_name'); // 1
            $table->string('applicant_name'); // 2
            $table->date('dob'); // 3
            $table->text('address'); // 4
            $table->string('unit_apt')->nullable(); // 5
            $table->string('city'); // 6
            $table->string('state'); // 7
            $table->string('zip_code'); // 8
            $table->string('phone'); // 9
            $table->string('phone2')->nullable(); // 10
            $table->string('email'); // 11
            $table->enum('gender', ['M', 'F']); // 12
            $table->string('ssn'); // 13
            $table->string('legal_status'); // 14
            $table->string('document_number'); // 15
            $table->string('insurance_company')->nullable(); // 16
            $table->string('insurance_plan')->nullable(); // 17
            $table->decimal('subsidy', 10, 2)->nullable(); // 18
            $table->decimal('final_cost', 10, 2)->nullable(); // 19
            $table->enum('employment_type', ['W2', '1099', 'Other'])->nullable(); // 20
            $table->string('employment_company_name')->nullable(); // 21
            $table->string('work_phone')->nullable(); // 22
            $table->decimal('wages', 10, 2)->nullable(); // 23
            $table->string('wages_frequency')->nullable(); // 24

            // Póliza Data (25-29)
            $table->string('poliza_number')->nullable(); // 25
            $table->string('poliza_category')->nullable(); // 26
            $table->decimal('poliza_amount', 10, 2)->nullable(); // 27
            $table->integer('poliza_payment_day')->nullable(); // 28
            $table->string('poliza_beneficiary')->nullable(); // 29

            // Person 1 Data (30-40 for Person 1)
            $table->string('person1_name')->nullable(); // 30
            $table->string('person1_relation')->nullable(); // 31
            $table->boolean('person1_is_applicant')->default(false); // 32
            $table->string('person1_legal_status')->nullable(); // 33
            $table->string('person1_document_number')->nullable(); // 34
            $table->date('person1_dob')->nullable(); // 35
            $table->string('person1_company_name')->nullable(); // 36
            $table->string('person1_ssn')->nullable(); // 37
            $table->enum('person1_gender', ['M', 'F'])->nullable(); // 38
            $table->decimal('person1_wages', 10, 2)->nullable(); // 39
            $table->string('person1_frequency')->nullable(); // 40

            // Person 2 Data
            $table->string('person2_name')->nullable();
            $table->string('person2_relation')->nullable();
            $table->boolean('person2_is_applicant')->default(false);
            $table->string('person2_legal_status')->nullable();
            $table->string('person2_document_number')->nullable();
            $table->date('person2_dob')->nullable();
            $table->string('person2_company_name')->nullable();
            $table->string('person2_ssn')->nullable();
            $table->enum('person2_gender', ['M', 'F'])->nullable();
            $table->decimal('person2_wages', 10, 2)->nullable();
            $table->string('person2_frequency')->nullable();

            // Person 3 Data
            $table->string('person3_name')->nullable();
            $table->string('person3_relation')->nullable();
            $table->boolean('person3_is_applicant')->default(false);
            $table->string('person3_legal_status')->nullable();
            $table->string('person3_document_number')->nullable();
            $table->date('person3_dob')->nullable();
            $table->string('person3_company_name')->nullable();
            $table->string('person3_ssn')->nullable();
            $table->enum('person3_gender', ['M', 'F'])->nullable();
            $table->decimal('person3_wages', 10, 2)->nullable();
            $table->string('person3_frequency')->nullable();

            // Person 4 Data
            $table->string('person4_name')->nullable();
            $table->string('person4_relation')->nullable();
            $table->boolean('person4_is_applicant')->default(false);
            $table->string('person4_legal_status')->nullable();
            $table->string('person4_document_number')->nullable();
            $table->date('person4_dob')->nullable();
            $table->string('person4_company_name')->nullable();
            $table->string('person4_ssn')->nullable();
            $table->enum('person4_gender', ['M', 'F'])->nullable();
            $table->decimal('person4_wages', 10, 2)->nullable();
            $table->string('person4_frequency')->nullable();

            // Payment Method Data (41-47)
            $table->string('card_type')->nullable(); // 41
            $table->string('card_number')->nullable(); // 42
            $table->string('card_expiration')->nullable(); // 43
            $table->string('card_cvv')->nullable(); // 44
            $table->string('bank_name')->nullable(); // 45
            $table->string('bank_routing')->nullable(); // 46
            $table->string('bank_account')->nullable(); // 47

            // Status and Confirmation
            $table->enum('status', ['Activo', 'Inactivo', 'En Revisión'])->default('En Revisión');
            $table->text('status_comment')->nullable();
            $table->boolean('confirmed')->default(false);

            $table->timestamps();

            // Indexes
            $table->index(['client_id', 'agent_id']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('application_forms');
    }
};
