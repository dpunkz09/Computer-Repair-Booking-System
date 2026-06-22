<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Notifications\QueuedResetPassword;
use App\Notifications\QueuedVerifyEmail;
use App\Support\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'profile_picture'])]
#[Hidden(['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes'])]
class User extends Authenticatable implements MustVerifyEmailContract
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, MustVerifyEmail, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function hasTwoFactorEnabled(): bool
    {
        return $this->two_factor_secret !== null && $this->two_factor_confirmed_at !== null;
    }

    public function isCustomer(): bool
    {
        return $this->role === UserRole::CUSTOMER;
    }

    public function isStaff(): bool
    {
        return ! $this->isCustomer();
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, UserRole::ADMIN_PANEL, true);
    }

    public function isFullAdmin(): bool
    {
        return $this->role === UserRole::ADMIN;
    }

    public function isDemoAdmin(): bool
    {
        return $this->role === UserRole::DEMO_ADMIN;
    }

    public function canManageSystemSettings(): bool
    {
        return $this->isFullAdmin();
    }

    public function canManageUsers(): bool
    {
        return $this->isFullAdmin();
    }

    public function canManageCategories(): bool
    {
        return $this->isFullAdmin();
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new QueuedVerifyEmail);
    }

    /**
     * Public URL for the user's profile picture, if set.
     */
    public function profilePictureUrl(): ?string
    {
        if (! $this->profile_picture) {
            return null;
        }

        return asset('storage/' . $this->profile_picture);
    }

    /**
     * Initials used when no profile picture is available.
     */
    public function initials(): string
    {
        $parts = preg_split('/\s+/', trim($this->name)) ?: [];

        if (count($parts) >= 2) {
            return strtoupper(substr($parts[0], 0, 1) . substr($parts[1], 0, 1));
        }

        return strtoupper(substr($this->name, 0, 2));
    }

    /**
     * Get the tickets created by this customer.
     */
    public function createdTickets()
    {
        return $this->hasMany(Ticket::class, 'customer_id');
    }

    /**
     * Get the tickets assigned to this technician.
     */
    public function assignedTickets()
    {
        return $this->hasMany(Ticket::class, 'technician_id');
    }

    /**
     * Get the comments created by this user.
     */
    public function comments()
    {
        return $this->hasMany(TicketComment::class);
    }

    /**
     * In-app notifications for this user.
     */
    public function userNotifications()
    {
        return $this->hasMany(UserNotification::class);
    }

    public function unreadNotificationsCount(): int
    {
        return $this->userNotifications()->whereNull('read_at')->count();
    }

    /**
     * Send the password reset notification via the queue.
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new QueuedResetPassword($token));
    }
}
