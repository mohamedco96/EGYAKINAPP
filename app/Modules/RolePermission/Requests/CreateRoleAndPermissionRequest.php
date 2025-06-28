<?php

namespace App\Modules\RolePermission\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateRoleAndPermissionRequest extends FormRequest
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
        $action = $this->input('action');

        $rules = [
            'action' => 'required|string|in:create_role,create_permission,assign_permission,remove_role,revoke_permission',
        ];

        switch ($action) {
            case 'create_role':
                $rules['role'] = 'required|string|max:255';
                break;

            case 'create_permission':
                $rules['permission'] = 'required|string|max:255';
                break;

            case 'assign_permission':
                $rules['role'] = 'required|string|max:255';
                $rules['permission'] = 'required|string|max:255';
                break;

            case 'remove_role':
                $rules['role'] = 'required|string|max:255';
                break;

            case 'revoke_permission':
                $rules['role'] = 'required|string|max:255';
                $rules['permission'] = 'required|string|max:255';
                break;
        }

        return $rules;
    }

    /**
     * Get custom error messages for validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'action.required' => 'Action is required.',
            'action.in' => 'Invalid action. Must be one of: create_role, create_permission, assign_permission, remove_role, revoke_permission.',
            'role.required' => 'Role name is required.',
            'role.string' => 'Role name must be a string.',
            'role.max' => 'Role name must not exceed 255 characters.',
            'permission.required' => 'Permission name is required.',
            'permission.string' => 'Permission name must be a string.',
            'permission.max' => 'Permission name must not exceed 255 characters.',
        ];
    }
}
