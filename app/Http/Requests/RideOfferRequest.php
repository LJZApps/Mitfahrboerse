<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RideOfferRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'zip_code' => 'required|string|max:10',
            'city' => 'required|string|max:255',
            'street' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'first_name' => 'nullable|string|max:255',
            'email' => 'required|email|max:255',
            'class' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:255',
            'valid_from' => 'nullable|date',
            'valid_until' => 'nullable|date|after_or_equal:valid_from',
            'cost_info' => 'nullable|string',
            'additional_info' => 'nullable|string',
        ];
    }
}
