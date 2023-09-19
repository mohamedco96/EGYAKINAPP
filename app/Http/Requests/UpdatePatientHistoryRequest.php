<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePatientHistoryRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'owner_id' => 'string|exists:App\Models\User,id',
            'NID' => 'string|size:14',
            'phone' => 'string|size:11',
            'email' => 'email|string',
        ];
    }

    public function messages()
    {
        return[
            'NID.size:14' => 'National ID should be 14 Number'
        ];
    }
}
