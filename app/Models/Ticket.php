<?php

namespace App\Models;

use Database\Factories\TicketFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['customer_id', 'technician_id', 'service_category_id', 'device_type', 'brand', 'os', 'issue_summary', 'description', 'status', 'priority', 'estimated_completion_at', 'cancelled_at'])]
class Ticket extends Model
{
    /** @use HasFactory<TicketFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'estimated_completion_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function isCancelled(): bool
    {
        return $this->cancelled_at !== null;
    }

    public function displayStatus(): string
    {
        return $this->isCancelled() ? 'cancelled' : $this->status;
    }

    public function canBeCancelledByCustomer(): bool
    {
        if ($this->cancelled_at !== null) {
            return false;
        }

        if (! in_array($this->status, ['new', 'assigned'], true)) {
            return false;
        }

        return ! $this->hasWorkStarted();
    }

    public function hasWorkStarted(): bool
    {
        if (in_array($this->status, ['in_progress', 'awaiting_parts', 'resolved', 'closed'], true)) {
            return true;
        }

        return $this->comments()
            ->whereHas('user', fn ($query) => $query->where('role', 'technician'))
            ->exists();
    }

    public function scopeNotCancelled($query)
    {
        return $query->whereNull('cancelled_at');
    }

    public function scopeCancelled($query)
    {
        return $query->whereNotNull('cancelled_at');
    }

    public function scopeUnassigned($query)
    {
        return $query->notCancelled()
            ->whereNull('technician_id')
            ->whereNotIn('status', \App\Support\TicketStatus::TERMINAL);
    }

    public function scopeOverdueEta($query)
    {
        return $query->notCancelled()
            ->whereNotNull('estimated_completion_at')
            ->where('estimated_completion_at', '<', now())
            ->whereNotIn('status', \App\Support\TicketStatus::TERMINAL);
    }
    /**
     * Get the service category for this ticket.
     */
    public function serviceCategory()
    {
        return $this->belongsTo(ServiceCategory::class);
    }

    /**
     * Get the customer who created this ticket.
     */
    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    /**
     * Get the technician assigned to this ticket.
     */
    public function technician()
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    /**
     * Get the comments on this ticket.
     */
    public function comments()
    {
        return $this->hasMany(TicketComment::class);
    }

    /**
     * Device photos uploaded with or after booking.
     */
    public function photos()
    {
        return $this->hasMany(TicketPhoto::class)->orderBy('sort_order');
    }
}
