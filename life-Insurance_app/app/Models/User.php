<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

final class User extends Authenticatable
{
    use HasFactory;

    public const ROLE_ADMIN = 'admin';
    public const ROLE_STAFF = 'staff';
    public const ROLE_AUDITOR = 'auditor';

    public const ROLES = [
        self::ROLE_ADMIN => '管理者',
        self::ROLE_STAFF => '一般職員',
        self::ROLE_AUDITOR => '監査担当者',
    ];

    protected $fillable = [
        'login_id',
        'password',
        'display_name',
        'role',
        'is_active',
        'must_change_password',
        'password_changed_at',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
            'must_change_password' => 'boolean',
            'password_changed_at' => 'datetime',
            'last_login_at' => 'datetime',
        ];
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class, 'assigned_user_id');
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isStaff(): bool
    {
        return $this->role === self::ROLE_STAFF;
    }

    public function isAuditor(): bool
    {
        return $this->role === self::ROLE_AUDITOR;
    }

    public function getRoleLabelAttribute(): string
    {
        return self::ROLES[$this->role] ?? $this->role;
    }
}
