<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('contact_us', function (Blueprint $table) {
            $table->id();
            $table->string('full_name', 100);
            $table->string('email', 100);
            $table->string('phone', 30);
            $table->string('zip_code', 20);
            $table->boolean('service_medical')->default(false);
            $table->boolean('service_dental')->default(false);
            $table->boolean('service_accidents')->default(false);
            $table->boolean('service_life')->default(false);
            $table->boolean('accept_sms')->default(false);
            $table->boolean('send_email')->default(false);
            $table->timestamp('email_sent_at')->nullable();
            $table->text('email_error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_us');
    }
};
