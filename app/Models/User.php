<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'type', // admin, agent, client
        'google_id',
        'avatar',
        'created_by',
        'updated_by',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    // Métodos para verificar tipo de usuario
    public function isAdmin()
    {
        return $this->type === 'admin';
    }

    public function isAgent()
    {
        return $this->type === 'agent';
    }

    public function isClient()
    {
        return $this->type === 'client';
    }

    // Relaciones para rastrear creación de usuarios
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function createdUsers()
    {
        return $this->hasMany(User::class, 'created_by');
    }

    // Relaciones para application forms
    public function applicationFormsAsClient()
    {
        return $this->hasMany(ApplicationForm::class, 'client_id');
    }

    public function applicationFormsAsAgent()
    {
        return $this->hasMany(ApplicationForm::class, 'agent_id');
    }

    public function uploadedDocuments()
    {
        return $this->hasMany(ApplicationDocument::class, 'uploaded_by');
    }
}