<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;


class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'emp_no',
        'emp_name',
        'role',
        'position',
        'title_class',
        'rank',
        'hr_job_name',
        'job_assigned',
        'emp_verified_at',
        'password',
        'avatar',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'name',
        'email',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'emp_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => 'string',
        ];
    }

    /**
     * Get the user's display name.
     */
    public function getNameAttribute(): string
    {
        return $this->emp_name ?? '';
    }

    /**
     * Get the user's email (using employee number for backward compatibility).
     */
    public function getEmailAttribute(): string
    {
        return $this->emp_no . '@company.com'; // or just return emp_no if you prefer
    }

    /**
     * Check if user is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user is a manager.
     */
    public function isManager(): bool
    {
        return $this->role === 'manager';
    }

    /**
     * Check if user is a moderator.
     */
    public function isModerator(): bool
    {
        return $this->role === 'moderator';
    }

    /**
     * Check if user has a specific role.
     */
    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    /**
     * Check if user has any of the given roles.
     */
    public function hasAnyRole(array $roles): bool
    {
        return in_array($this->role, $roles);
    }
    
    /**
     * Get the role details from the roles table.
     */
    public function roleDetails()
    {
        return $this->belongsTo(Role::class, 'role', 'slug');
    }

    /**
     * Check if user is verified.
     */
    public function isVerified(): bool
    {
        return !is_null($this->emp_verified_at);
    }
}
