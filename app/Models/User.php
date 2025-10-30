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
        'created_by_admin',
        'updated_by',
        'agent_id', // Agente asignado al cliente
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

    public function createdByAdmin()
    {
        return $this->belongsTo(User::class, 'created_by_admin');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function createdUsers()
    {
        return $this->hasMany(User::class, 'created_by');
    }

    // Relación: agente asignado al cliente
    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    // Relación: clientes asignados a un agente
    public function assignedClients()
    {
        return $this->hasMany(User::class, 'agent_id');
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