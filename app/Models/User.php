<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'firstname',
        'middlename',
        'lastname',
        'email',
        'password',
        'role',
        'contact_number',
        'account_status',
        'valid_id_path',
        'rejection_reason',
        'profile_image_path',
        'approved_at',
        'rejected_at',
        'is_active',
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
     * @var array
     */
    protected $appends = ['full_name', 'profile_image_url', 'valid_id_url'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get the user's full name.
     */
    public function getFullNameAttribute(): string
    {
        $name = "{$this->firstname}";
        if ($this->middlename) {
            $name .= " {$this->middlename}";
        }
        $name .= " {$this->lastname}";
        return $name;
    }

    /**
     * Get the full URL for the profile image.
     */
    public function getProfileImageUrlAttribute(): ?string
    {
        if (!$this->profile_image_path) {
            return null;
        }
        return asset('storage/' . $this->profile_image_path);
    }

    /**
     * Get the full URL for the valid ID.
     */
    public function getValidIdUrlAttribute(): ?string
    {
        if (!$this->valid_id_path) {
            return null;
        }
        return asset('storage/' . $this->valid_id_path);
    }

    /**
     * Check if user is admin
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user is client
     */
    public function isClient(): bool
    {
        return $this->role === 'client';
    }

    /**
     * Check if account is approved
     */
    public function isApproved(): bool
    {
        return $this->account_status === 'approved';
    }

    /**
     * Check if account is pending
     */
    public function isPending(): bool
    {
        return $this->account_status === 'pending';
    }

    /**
     * Check if account is rejected
     */
    public function isRejected(): bool
    {
        return $this->account_status === 'rejected';
    }

    /**
     * Get all notifications for this user.
     */
    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    /**
     * Get appointments for this user (as client).
     */
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'client_id');
    }

    /**
     * Get projects for this user (as client).
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class, 'client_id');
    }

    /**
     * Get activity logs for this user.
     */
    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    /**
     * Get all unread notifications.
     */
    public function unreadNotifications(): HasMany
    {
        return $this->notifications()->where('is_read', false);
    }
}