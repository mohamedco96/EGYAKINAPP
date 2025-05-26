<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class RolePermissionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->hasRole('admin');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        $rules = [
            'action' => 'required|string|in:create_role,create_permission,assign_permission,remove_role,revoke_permission,assign_role,assign_permission',
        ];

        // Add conditional rules based on action
        if (in_array($this->input('action'), ['create_role', 'assign_permission', 'remove_role', 'revoke_permission'])) {
            $rules['role'] = 'required|string|max:255|regex:/^[a-zA-Z0-9_-]+$/';
        }

        if (in_array($this->input('action'), ['create_permission', 'assign_permission', 'revoke_permission'])) {
            $rules['permission'] = 'required|string|max:255|regex:/^[a-zA-Z0-9_-]+$/';
        }

        if (in_array($this->input('action'), ['assign_role', 'assign_permission'])) {
            $rules['roleOrPermission'] = 'required|string|max:255|regex:/^[a-zA-Z0-9_-]+$/';
            $rules['user_id'] = 'required|exists:users,id';
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'action.required' => 'Action is required',
            'action.in' => 'Invalid action specified',
            'role.required' => 'Role name is required',
            'role.regex' => 'Role name can only contain letters, numbers, underscores, and hyphens',
            'permission.required' => 'Permission name is required',
            'permission.regex' => 'Permission name can only contain letters, numbers, underscores, and hyphens',
            'roleOrPermission.required' => 'Role or permission name is required',
            'roleOrPermission.regex' => 'Role or permission name can only contain letters, numbers, underscores, and hyphens',
            'user_id.required' => 'User ID is required',
            'user_id.exists' => 'Specified user does not exist',
        ];
    }

    /**
     * Handle a failed validation attempt.
     *
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        throw new ValidationException($validator, response()->json([
            'value' => false,
            'message' => array_values($validator->errors()->toArray())[0][0],
            'errors' => $validator->errors(),
        ], 422));
    }
}
