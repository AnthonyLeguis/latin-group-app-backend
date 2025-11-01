<?php

namespace App\Data\Application;

use Spatie\LaravelData\Data;

class ApplicationFormData extends Data
{
    public function __construct(
        // Application Data (1-24)
        public string $applicant_name,
        public string $dob, // Date of Birth
        public string $address,
        public ?string $unit_apt,
        public string $city,
        public string $state,
        public string $zip_code,
        public string $phone,
        public ?string $phone2,
        public string $email,
        public string $gender, // M/F
        public ?string $ssn, // Ahora opcional
        public string $legal_status,
        public ?string $document_number, // Ahora opcional
        public ?string $insurance_company,
        public ?string $insurance_plan,
        public ?float $subsidy,
        public ?float $final_cost, // Label en frontend: "Costo de la Prima"
        public ?string $employment_type, // W2/1099/Other
        public ?string $employment_company_name,
        public ?string $work_phone,
        public ?float $wages,
        public ?string $wages_frequency,

        // Póliza Data (25-29)
        public ?string $poliza_number,
        public ?string $poliza_category, // Label en frontend: "Póliza Dental"
        public ?string $poliza_key, // Nuevo campo: "Clave"
        public ?float $poliza_amount, // Label en frontend: "Monto Prima Dental"
        public ?int $poliza_payment_day,
        public ?string $poliza_beneficiary,

        // Person 1 Data (30-40)
        public ?string $person1_name,
        public ?string $person1_relation,
        public ?bool $person1_is_applicant,
        public ?string $person1_legal_status,
        public ?string $person1_document_number,
        public ?string $person1_dob,
        public ?string $person1_company_name,
        public ?string $person1_ssn,
        public ?string $person1_gender,
        public ?float $person1_wages,
        public ?string $person1_frequency,

        // Person 2 Data
        public ?string $person2_name,
        public ?string $person2_relation,
        public ?bool $person2_is_applicant,
        public ?string $person2_legal_status,
        public ?string $person2_document_number,
        public ?string $person2_dob,
        public ?string $person2_company_name,
        public ?string $person2_ssn,
        public ?string $person2_gender,
        public ?float $person2_wages,
        public ?string $person2_frequency,

        // Person 3 Data
        public ?string $person3_name,
        public ?string $person3_relation,
        public ?bool $person3_is_applicant,
        public ?string $person3_legal_status,
        public ?string $person3_document_number,
        public ?string $person3_dob,
        public ?string $person3_company_name,
        public ?string $person3_ssn,
        public ?string $person3_gender,
        public ?float $person3_wages,
        public ?string $person3_frequency,

        // Person 4 Data
        public ?string $person4_name,
        public ?string $person4_relation,
        public ?bool $person4_is_applicant,
        public ?string $person4_legal_status,
        public ?string $person4_document_number,
        public ?string $person4_dob,
        public ?string $person4_company_name,
        public ?string $person4_ssn,
        public ?string $person4_gender,
        public ?float $person4_wages,
        public ?string $person4_frequency,

        // Person 5 Data
        public ?string $person5_name,
        public ?string $person5_relation,
        public ?bool $person5_is_applicant,
        public ?string $person5_legal_status,
        public ?string $person5_document_number,
        public ?string $person5_dob,
        public ?string $person5_company_name,
        public ?string $person5_ssn,
        public ?string $person5_gender,
        public ?float $person5_wages,
        public ?string $person5_frequency,

        // Person 6 Data
        public ?string $person6_name,
        public ?string $person6_relation,
        public ?bool $person6_is_applicant,
        public ?string $person6_legal_status,
        public ?string $person6_document_number,
        public ?string $person6_dob,
        public ?string $person6_company_name,
        public ?string $person6_ssn,
        public ?string $person6_gender,
        public ?float $person6_wages,
        public ?string $person6_frequency,

        // Payment Method Data (41-47)
        public ?string $card_type,
        public ?string $card_number,
        public ?string $card_expiration,
        public ?string $card_cvv,
        public ?string $bank_name,
        public ?string $bank_routing,
        public ?string $bank_account,

        // Status and Confirmation
        public ?string $status, // Activo, Inactivo, En Revisión
        public ?string $status_comment,
        public ?bool $confirmed,
        
        // Rejection tracking
        public ?string $rejection_reason,
        public ?string $rejected_at,
    ) {}

    public static function rules(): array
    {
        return [
            // Application Data Validation
            'applicant_name' => 'required|string|max:255',
            'dob' => 'required|date|before:today',
            'address' => 'required|string|max:500',
            'unit_apt' => 'nullable|string|max:50',
            'city' => 'required|string|max:100',
            'state' => 'required|string|max:50',
            'zip_code' => 'required|string|max:20',
            'phone' => 'required|string|max:20',
            'phone2' => 'nullable|string|max:20',
            'email' => 'required|email|max:255',
            'gender' => 'required|in:M,F',
            'ssn' => 'nullable|string|max:20', // Cambiado a nullable
            'legal_status' => 'required|string|max:100',
            'document_number' => 'nullable|string|max:50', // Cambiado a nullable
            'insurance_company' => 'nullable|string|max:255',
            'insurance_plan' => 'nullable|string|max:255',
            'subsidy' => 'nullable|numeric|min:0',
            'final_cost' => 'nullable|numeric|min:0',
            'employment_type' => 'nullable|in:W2,1099,Other',
            'employment_company_name' => 'nullable|string|max:255',
            'work_phone' => 'nullable|string|max:20',
            'wages' => 'nullable|numeric|min:0',
            'wages_frequency' => 'nullable|string|max:50',

            // Póliza Data Validation
            'poliza_number' => 'nullable|string|max:100',
            'poliza_category' => 'nullable|string|max:100',
            'poliza_key' => 'nullable|string|max:100',
            'poliza_amount' => 'nullable|numeric|min:0',
            'poliza_payment_day' => 'nullable|integer|min:1|max:31',
            'poliza_beneficiary' => 'nullable|string|max:255',

            // Person 1 Validation
            'person1_name' => 'nullable|string|max:255',
            'person1_relation' => 'nullable|string|max:100',
            'person1_is_applicant' => 'nullable|boolean',
            'person1_legal_status' => 'nullable|string|max:100',
            'person1_document_number' => 'nullable|string|max:50',
            'person1_dob' => 'nullable|date|before:today',
            'person1_company_name' => 'nullable|string|max:255',
            'person1_ssn' => 'nullable|string|max:20',
            'person1_gender' => 'nullable|in:M,F',
            'person1_wages' => 'nullable|numeric|min:0',
            'person1_frequency' => 'nullable|string|max:50',

            // Person 2 Validation (same as person 1)
            'person2_name' => 'nullable|string|max:255',
            'person2_relation' => 'nullable|string|max:100',
            'person2_is_applicant' => 'nullable|boolean',
            'person2_legal_status' => 'nullable|string|max:100',
            'person2_document_number' => 'nullable|string|max:50',
            'person2_dob' => 'nullable|date|before:today',
            'person2_company_name' => 'nullable|string|max:255',
            'person2_ssn' => 'nullable|string|max:20',
            'person2_gender' => 'nullable|in:M,F',
            'person2_wages' => 'nullable|numeric|min:0',
            'person2_frequency' => 'nullable|string|max:50',

            // Person 3 Validation (same as person 1)
            'person3_name' => 'nullable|string|max:255',
            'person3_relation' => 'nullable|string|max:100',
            'person3_is_applicant' => 'nullable|boolean',
            'person3_legal_status' => 'nullable|string|max:100',
            'person3_document_number' => 'nullable|string|max:50',
            'person3_dob' => 'nullable|date|before:today',
            'person3_company_name' => 'nullable|string|max:255',
            'person3_ssn' => 'nullable|string|max:20',
            'person3_gender' => 'nullable|in:M,F',
            'person3_wages' => 'nullable|numeric|min:0',
            'person3_frequency' => 'nullable|string|max:50',

            // Person 4 Validation (same as person 1)
            'person4_name' => 'nullable|string|max:255',
            'person4_relation' => 'nullable|string|max:100',
            'person4_is_applicant' => 'nullable|boolean',
            'person4_legal_status' => 'nullable|string|max:100',
            'person4_document_number' => 'nullable|string|max:50',
            'person4_dob' => 'nullable|date|before:today',
            'person4_company_name' => 'nullable|string|max:255',
            'person4_ssn' => 'nullable|string|max:20',
            'person4_gender' => 'nullable|in:M,F',
            'person4_wages' => 'nullable|numeric|min:0',
            'person4_frequency' => 'nullable|string|max:50',

            // Person 5 Validation (same as person 1)
            'person5_name' => 'nullable|string|max:255',
            'person5_relation' => 'nullable|string|max:100',
            'person5_is_applicant' => 'nullable|boolean',
            'person5_legal_status' => 'nullable|string|max:100',
            'person5_document_number' => 'nullable|string|max:50',
            'person5_dob' => 'nullable|date|before:today',
            'person5_company_name' => 'nullable|string|max:255',
            'person5_ssn' => 'nullable|string|max:20',
            'person5_gender' => 'nullable|in:M,F',
            'person5_wages' => 'nullable|numeric|min:0',
            'person5_frequency' => 'nullable|string|max:50',

            // Person 6 Validation (same as person 1)
            'person6_name' => 'nullable|string|max:255',
            'person6_relation' => 'nullable|string|max:100',
            'person6_is_applicant' => 'nullable|boolean',
            'person6_legal_status' => 'nullable|string|max:100',
            'person6_document_number' => 'nullable|string|max:50',
            'person6_dob' => 'nullable|date|before:today',
            'person6_company_name' => 'nullable|string|max:255',
            'person6_ssn' => 'nullable|string|max:20',
            'person6_gender' => 'nullable|in:M,F',
            'person6_wages' => 'nullable|numeric|min:0',
            'person6_frequency' => 'nullable|string|max:50',

            // Payment Method Validation
            'card_type' => 'nullable|string|max:50',
            'card_number' => 'nullable|string|max:25',
            'card_expiration' => 'nullable|string|max:10',
            'card_cvv' => 'nullable|string|max:5',
            'bank_name' => 'nullable|string|max:255',
            'bank_routing' => 'nullable|string|max:20',
            'bank_account' => 'nullable|string|max:30',

            // Status and Confirmation
            'status' => 'nullable|in:Activo,Inactivo,En Revisión',
            'status_comment' => 'nullable|string|max:1000',
            'confirmed' => 'nullable|boolean',
        ];
    }
}