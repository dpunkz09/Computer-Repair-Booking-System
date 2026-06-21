<?php

namespace App\Http\Requests;

use App\Support\TicketStatus;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTicketRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => 'required|'.TicketStatus::ruleInWorkflow(),
            'priority' => 'nullable|integer|between:1,5',
            'estimated_completion_at' => 'nullable|date|after:now',
        ];
    }
}
