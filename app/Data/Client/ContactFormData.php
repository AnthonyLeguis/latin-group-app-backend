<?php

namespace App\Data\Client;

class ContactFormData
{
    public string $fullName;
    public string $email;
    public string $phone;
    public string $zipCode;
    public bool $serviceMedical;
    public bool $serviceDental;
    public bool $serviceAccidents;
    public bool $serviceLife;
    public bool $acceptSms;

    public function __construct(array $data)
    {
        $this->fullName = $data['fullName'] ?? '';
        $this->email = $data['email'] ?? '';
        $this->phone = $data['phone'] ?? '';
        $this->zipCode = $data['zipCode'] ?? '';
        $this->serviceMedical = (bool)($data['serviceMedical'] ?? false);
        $this->serviceDental = (bool)($data['serviceDental'] ?? false);
        $this->serviceAccidents = (bool)($data['serviceAccidents'] ?? false);
        $this->serviceLife = (bool)($data['serviceLife'] ?? false);
        $this->acceptSms = (bool)($data['acceptSms'] ?? false);
    }

    public function atLeastOneServiceSelected(): bool
    {
        return $this->serviceMedical || $this->serviceDental || $this->serviceAccidents || $this->serviceLife;
    }
}
