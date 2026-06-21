<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['ticket_id', 'user_id', 'comment_text', 'is_internal_note'])]
class TicketComment extends Model
{
    /**
     * Get the ticket this comment belongs to.
     */
    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * Get the user who created this comment.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
