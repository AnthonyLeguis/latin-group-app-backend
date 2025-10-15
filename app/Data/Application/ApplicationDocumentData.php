<?php

namespace App\Data\Application;

use Spatie\LaravelData\Data;

class ApplicationDocumentData extends Data
{
    public function __construct(
        public int $application_form_id,
        public string $document_type, // cedula, recibo, contrato, etc.
    ) {}

    public static function rules(): array
    {
        return [
            'application_form_id' => 'required|exists:application_forms,id',
            'document_type' => 'required|string|max:100',
        ];
    }
}