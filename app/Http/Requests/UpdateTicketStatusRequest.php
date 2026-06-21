<?php

namespace App\Http\Requests;

use App\Support\TicketStatus;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTicketStatusRequest extends FormRequest
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
            'status' => 'required|'.TicketStatus::ruleInTechnicianQuickUpdate(),
        ];
    }
}
