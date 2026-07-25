<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Attributes\Casts;
use App\Concerns\BelongsToTenant;
use App\Concerns\HasAttributeCasts;
use App\Concerns\HasConversations;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
/**
 * @property int $id
 * @property int $tenant_id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
use Laravel\Fortify\TwoFactorAuthenticatable;

#[Fillable(['name', 'email', 'password', 'tenant_id', 'is_supervisor', 'role'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
#[Casts([
    'email_verified_at' => 'datetime',
    'password' => 'hashed',
    'two_factor_confirmed_at' => 'datetime',
    'is_supervisor' => 'boolean',
])]
class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use BelongsToTenant, HasAttributeCasts, HasConversations, HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    public function getConnectionName()
    {
        return config('database.master_connection', 'sqlite');
    }

    /**
     * Determine if the user is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin' || (! $this->role && ! $this->is_supervisor);
    }

    /**
     * Determine if the user has supervisor permissions.
     */
    public function isSupervisor(): bool
    {
        return (bool) ($this->is_supervisor ?? false) || in_array($this->role, ['supervisor', 'admin']);
    }

    /**
     * Determine if the user is a field technician.
     */
    public function isTechnician(): bool
    {
        return $this->role === 'technician' && ! $this->is_supervisor;
    }

    /**
     * Check if user has specific role or roles.
     */
    public function hasRole(string|array $roles): bool
    {
        $roles = (array) $roles;
        if (in_array('admin', $roles) && $this->isAdmin()) {
            return true;
        }

        return in_array($this->role, $roles);
    }

    /**
     * Get the tenant that owns the user.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the employee profile associated with the user.
     */
    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class);
    }
}
