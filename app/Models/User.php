<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Notifications\EmployeeSetPasswordNotification;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
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
        'role',
    ];

    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }

    public function hasRole(string $role): bool
    {
        return $this->roles->contains('name', $role);
    }

    public function hasAnyRole(array $roles): bool
    {
        return $this->roles->whereIn('name', $roles)->isNotEmpty();
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function isAdmin(): bool
    {
        return $this->hasAnyRole(['admin', 'hr']);
    }

    public function isEmployee(): bool
    {
        return $this->employee !== null;
    }

    public function homeUrl(): string
    {
        if ($this->employee !== null) {
            return route('attendance.index');
        }

        if ($this->hasAnyRole(['admin', 'hr'])) {
            return route('dashboard');
        }

        return route('login');
    }

    public function canAccessPath(string $url): bool
    {
        $path = '/'.trim(parse_url($url, PHP_URL_PATH) ?? '', '/');

        if ($this->isAdmin()) {
            foreach (['/dashboard', '/employees', '/admin', '/settings', '/payrolls', '/profile'] as $prefix) {
                if ($path === $prefix || str_starts_with($path, $prefix.'/')) {
                    return true;
                }
            }
            if (preg_match('#^/attendance/\d+/doctor-note$#', $path)) {
                return true;
            }
        }

        if ($this->employee !== null) {
            foreach (['/attendance', '/profile', '/my-payrolls'] as $prefix) {
                if ($path === $prefix || str_starts_with($path, $prefix.'/')) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'face_descriptor',
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
            'face_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new EmployeeSetPasswordNotification($token));
    }

    public function employee()
    {
        return $this->hasOne(Employee::class);
    }

    public function hasFaceRegistered(): bool
    {
        return $this->employee?->canVerifyFace() ?? false;
    }
}
