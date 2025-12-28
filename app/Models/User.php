<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function brand(): HasOne
    {
        return $this->hasOne(Brand::class);
    }

    public function venue(): HasOne
    {
        return $this->hasOne(Venue::class);
    }

    public function producer(): HasOne
    {
        return $this->hasOne(Producer::class);
    }

    public function threePL(): HasOne
    {
        return $this->hasOne(ThreePL::class);
    }

    public function getPrimaryRole(): ?string
    {
        return $this->roles->first()?->name;
    }

    public function dashboardRoute(): string
    {
        $role = $this->getPrimaryRole();

        return match ($role) {
            'admin' => 'admin.dashboard',
            'brand' => 'brand.dashboard',
            'venue' => 'venue.dashboard',
            'producer' => 'producer.dashboard',
            '3pl' => 'threePL.dashboard',
            default => 'dashboard',
        };
    }
}
