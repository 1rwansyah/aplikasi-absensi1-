<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Employee extends Model
{
    protected $casts = [
        'birth_date' => 'date',
        'join_date' => 'date',
    ];

    protected $fillable = [
        'user_id',
        'default_work_schedule_id',
        'employee_code',
        'name',
        'profile_photo',
        'face_descriptor',
        'nik',
        'birth_place',
        'birth_date',
        'address',
        'education',
        'work_experience',
        'email',
        'position',
        'staff',
        'join_date',
        'employment_status',
        'basic_salary',
        'bank_name',
        'bank_account_number',
        'bank_account_name',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function salaryComponents()
    {
        return $this->hasMany(EmployeeSalaryComponent::class);
    }

    public function getGrossSalaryAttribute()
    {
        $active = $this->salaryComponents->where('is_active', true);

        $allowance = $active
            ->filter(fn ($item) => $item->salaryComponent?->type === 'allowance')
            ->sum('amount');

        return ($this->basic_salary ?? 0) + $allowance;
    }

    public function payrolls()
    {
        return $this->hasMany(Payroll::class);
    }

    public static function generateCode(): string
    {
        $latest = static::where('employee_code', 'like', 'ID-%')
            ->orderByRaw('CAST(SUBSTRING(employee_code, 4) AS UNSIGNED) DESC')
            ->value('employee_code');

        $next = $latest ? (int) substr($latest, 3) + 1 : 1;

        return 'ID-'.str_pad($next, 3, '0', STR_PAD_LEFT);
    }

    public function hasProfilePhoto(): bool
    {
        if (! filled($this->profile_photo)) {
            return false;
        }

        return Storage::disk('public')->exists($this->profile_photo);
    }

    public function profilePhotoUrl(): ?string
    {
        if (! $this->hasProfilePhoto()) {
            return null;
        }

        $path = str_replace('\\', '/', (string) $this->profile_photo);

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return route('employees.profile-photo.show', $this);
    }

    public function faceDescriptorArray(): ?array
    {
        if (! filled($this->face_descriptor)) {
            return null;
        }

        $decoded = json_decode($this->face_descriptor, true);

        return is_array($decoded) ? $decoded : null;
    }

    public function hasFaceDescriptor(): bool
    {
        return $this->faceDescriptorArray() !== null;
    }

    public function canVerifyFace(): bool
    {
        return $this->hasProfilePhoto() && $this->hasFaceDescriptor();
    }

    public function schedules()
    {
        return $this->hasMany(EmployeeSchedule::class);
    }

    public function defaultWorkSchedule()
    {
        return $this->belongsTo(WorkSchedule::class, 'default_work_schedule_id');
    }
}
