<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTicketDetailsRequest extends FormRequest
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
            'device_type' => 'required|string|max:255',
            'brand' => 'required|string|max:255',
            'os' => 'required|string|max:255',
            'issue_summary' => 'required|string|max:255',
            'description' => 'required|string|max:10000',
        ];
    }
}
