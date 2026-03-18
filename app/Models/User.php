<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\Report;
use App\Models\ActivityLog;

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
        'name',
        'email',
        'password',
        'department',
        'division',
        'project',
        'bureau',
        'office',
        'institution',
        'avatar_path',
        'signature_path',
        'position',
        'role',
        'status',
        'otp_code',
        'otp_hash',
        'otp_expiration',
    ];

    public const ADMIN_ROLES = ['admin', 'hr-super-admin', 'ph-admin'];
    public const STAFF_ROLES = ['staff'];

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
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'otp_expiration' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function reports()
    {
        return $this->hasMany(Report::class);
    }

    public function reviewedReports()
    {
        return $this->hasMany(Report::class, 'reviewed_by');
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }

    // Backward-compatible alias: app code still uses "department" while DB uses "bureau".
    public function getDepartmentAttribute(): ?string
    {
        return $this->attributes['bureau'] ?? null;
    }

    public function setDepartmentAttribute(?string $value): void
    {
        $this->attributes['bureau'] = $value;
    }

    public static function isAdminRole(?string $role): bool
    {
        return in_array((string) $role, self::ADMIN_ROLES, true);
    }
}
