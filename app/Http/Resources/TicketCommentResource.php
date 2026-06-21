<?php

namespace App\Http\Resources;

use App\Models\TicketComment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin TicketComment */
class TicketCommentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'author_name' => $this->user->name,
            'author_role' => ucfirst($this->user->role),
            'author_initials' => $this->user->initials(),
            'author_avatar' => $this->user->profilePictureUrl(),
            'is_internal_note' => (bool) $this->is_internal_note,
            'body' => $this->comment_text,
            'time_ago' => $this->created_at->diffForHumans(),
        ];
    }
}
