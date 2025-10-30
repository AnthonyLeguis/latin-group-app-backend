<?php

namespace App\Data\Auth;

use Spatie\LaravelData\Data;

class ChangePasswordData extends Data
{
    public function __construct(
        public string $current_password,
        public string $new_password,
        public string $new_password_confirmation,
    ) {}
}
