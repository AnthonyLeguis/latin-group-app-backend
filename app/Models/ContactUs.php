<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ContactUs extends Model
{
    use HasFactory;

    protected $table = 'contact_us';

    protected $fillable = [
        'full_name',
        'email',
        'phone',
        'zip_code',
        'service_medical',
        'service_dental',
        'service_accidents',
        'service_life',
        'accept_sms',
        'send_email',
        'email_sent_at',
        'email_error',
    ];

    protected $casts = [
        'service_medical' => 'boolean',
        'service_dental' => 'boolean',
        'service_accidents' => 'boolean',
        'service_life' => 'boolean',
        'accept_sms' => 'boolean',
        'send_email' => 'boolean',
        'email_sent_at' => 'datetime',
    ];
}
