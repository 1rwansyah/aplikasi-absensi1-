<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class CompanyProfile extends Model
{
    protected $fillable = [
        'name',
        'logo',
        'address',
        'email',
        'phone',
        'website',
        'description',
    ];

    /**
     * Get the first (and only) company profile
     */
    public static function getProfile(): ?self
    {
        return self::first();
    }

    /**
     * Get logo URL
     */
    public function getLogoUrlAttribute(): ?string
    {
        if (! $this->logo) {
            return null;
        }

        return Storage::disk('public')->url($this->logo);
    }

    /**
     * Check if logo exists
     */
    public function hasLogo(): bool
    {
        if (! $this->logo) {
            return false;
        }

        return Storage::disk('public')->exists($this->logo);
    }
}
