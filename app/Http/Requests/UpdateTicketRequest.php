<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTicketRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->role === 'agent';
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'status'             => ['nullable', 'in:open,in_progress,resolved,closed'],
            'priority'           => ['nullable', 'in:low,normal,high'],
            'assigned_to_user_id' => ['nullable', 'exists:users,id'],
        ];
    }
}
