<?php

namespace App\Modules\RolePermission\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionService
{
    /**
     * Create role or permission, or assign permission to role
     */
    public function createRoleAndPermission(array $data): array
    {
        try {
            $action = $data['action'];

            switch ($action) {
                case 'create_role':
                    return $this->createRole($data);

                case 'create_permission':
                    return $this->createPermission($data);

                case 'assign_permission':
                    return $this->assignPermissionToRole($data);

                case 'remove_role':
                    return $this->removeRole($data);

                case 'revoke_permission':
                    return $this->revokePermissionFromRole($data);

                default:
                    Log::warning('Invalid action attempted', ['action' => $action]);

                    return [
                        'success' => false,
                        'data' => [
                            'value' => false,
                            'message' => 'Invalid action!',
                        ],
                        'status_code' => 400,
                    ];
            }
        } catch (\Exception $e) {
            Log::error('Error in createRoleAndPermission: '.$e->getMessage(), [
                'data' => $data,
                'exception' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'data' => [
                    'value' => false,
                    'message' => 'Failed to process role/permission action',
                ],
                'status_code' => 500,
            ];
        }
    }

    /**
     * Assign role or permission to user
     */
    public function assignRoleToUser(array $data): array
    {
        try {
            $doctorId = Auth::id();
            $user = User::find($doctorId);

            if (! $user) {
                Log::error('User not found for assignment', ['doctor_id' => $doctorId]);

                return [
                    'success' => false,
                    'data' => [
                        'value' => false,
                        'message' => 'User not found',
                    ],
                    'status_code' => 404,
                ];
            }

            $action = $data['action'];

            switch ($action) {
                case 'assign_role':
                    return $this->assignRoleToUserInternal($user, $data);

                case 'assign_permission':
                    return $this->assignPermissionToUserInternal($user, $data);

                default:
                    Log::warning('Invalid assignment action attempted', [
                        'action' => $action,
                        'user_id' => $doctorId,
                    ]);

                    return [
                        'success' => false,
                        'data' => [
                            'value' => false,
                            'message' => 'Invalid action!',
                        ],
                        'status_code' => 400,
                    ];
            }
        } catch (\Exception $e) {
            Log::error('Error in assignRoleToUser: '.$e->getMessage(), [
                'data' => $data,
                'exception' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'data' => [
                    'value' => false,
                    'message' => 'Failed to assign role/permission to user',
                ],
                'status_code' => 500,
            ];
        }
    }

    /**
     * Check role and permission for current user
     */
    public function checkRoleAndPermission(): array
    {
        try {
            $user = Auth::user();

            if (! $user) {
                return [
                    'success' => false,
                    'data' => [
                        'value' => false,
                        'message' => 'User not authenticated',
                    ],
                    'status_code' => 401,
                ];
            }

            // Get all user permissions
            $allPermissions = $user->getAllPermissions()->pluck('name')->unique()->values();
            $roles = $user->roles->pluck('name');

            Log::info('Role and permission check performed', [
                'user_id' => $user->id,
                'roles' => $roles,
                'permissions_count' => $allPermissions->count(),
            ]);

            return [
                'success' => true,
                'data' => [
                    'value' => true,
                    'message' => 'User permissions retrieved successfully',
                    'roles' => $roles,
                    'permissions' => $allPermissions,
                    'has_admin_role' => $user->hasRole('admin'),
                    'has_super_admin_role' => $user->hasRole('super-admin'),
                ],
                'status_code' => 200,
            ];

        } catch (\Exception $e) {
            Log::error('Error in checkRoleAndPermission: '.$e->getMessage(), [
                'exception' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'data' => [
                    'value' => false,
                    'message' => 'Failed to check role and permission',
                ],
                'status_code' => 500,
            ];
        }
    }

    /**
     * Create a new role
     */
    private function createRole(array $data): array
    {
        $roleName = $data['role'];

        if (Role::where('name', $roleName)->exists()) {
            return [
                'success' => false,
                'data' => [
                    'value' => false,
                    'message' => 'Role already exists!',
                ],
                'status_code' => 409,
            ];
        }

        $role = Role::create(['name' => $roleName]);

        Log::info('Role created successfully', ['role_name' => $roleName, 'role_id' => $role->id]);

        return [
            'success' => true,
            'data' => [
                'value' => true,
                'message' => 'Role created successfully!',
                'role' => $role,
            ],
            'status_code' => 201,
        ];
    }

    /**
     * Create a new permission
     */
    private function createPermission(array $data): array
    {
        $permissionName = $data['permission'];

        if (Permission::where('name', $permissionName)->exists()) {
            return [
                'success' => false,
                'data' => [
                    'value' => false,
                    'message' => 'Permission already exists!',
                ],
                'status_code' => 409,
            ];
        }

        $permission = Permission::create(['name' => $permissionName]);

        Log::info('Permission created successfully', [
            'permission_name' => $permissionName,
            'permission_id' => $permission->id,
        ]);

        return [
            'success' => true,
            'data' => [
                'value' => true,
                'message' => 'Permission created successfully!',
                'permission' => $permission,
            ],
            'status_code' => 201,
        ];
    }

    /**
     * Assign permission to role
     */
    private function assignPermissionToRole(array $data): array
    {
        // Validate the request data
        $validator = Validator::make($data, [
            'role' => 'required|string',
            'permission' => 'required|string',
        ]);

        if ($validator->fails()) {
            return [
                'success' => false,
                'data' => [
                    'value' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ],
                'status_code' => 400,
            ];
        }

        $roleName = $data['role'];
        $permissionName = $data['permission'];

        $role = Role::findByName($roleName);
        $permission = Permission::findByName($permissionName);

        if (! $role || ! $permission) {
            return [
                'success' => false,
                'data' => [
                    'value' => false,
                    'message' => 'Role or Permission not found!',
                ],
                'status_code' => 404,
            ];
        }

        if ($role->hasPermissionTo($permission)) {
            return [
                'success' => false,
                'data' => [
                    'value' => false,
                    'message' => 'Permission already assigned to role!',
                ],
                'status_code' => 409,
            ];
        }

        $role->givePermissionTo($permission);

        Log::info('Permission assigned to role successfully', [
            'role_name' => $roleName,
            'permission_name' => $permissionName,
        ]);

        return [
            'success' => true,
            'data' => [
                'value' => true,
                'message' => 'Permission assigned to role successfully!',
            ],
            'status_code' => 200,
        ];
    }

    /**
     * Remove a role (placeholder for future implementation)
     */
    private function removeRole(array $data): array
    {
        // Placeholder for future implementation
        return [
            'success' => false,
            'data' => [
                'value' => false,
                'message' => 'Remove role functionality not implemented yet',
            ],
            'status_code' => 501,
        ];
    }

    /**
     * Revoke permission from role (placeholder for future implementation)
     */
    private function revokePermissionFromRole(array $data): array
    {
        // Placeholder for future implementation
        return [
            'success' => false,
            'data' => [
                'value' => false,
                'message' => 'Revoke permission functionality not implemented yet',
            ],
            'status_code' => 501,
        ];
    }

    /**
     * Assign role to user internal method
     */
    private function assignRoleToUserInternal(User $user, array $data): array
    {
        $validator = Validator::make($data, [
            'roleOrPermission' => 'required|string',
        ]);

        if ($validator->fails()) {
            return [
                'success' => false,
                'data' => [
                    'value' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ],
                'status_code' => 400,
            ];
        }

        $roleOrPermission = $data['roleOrPermission'];

        if ($user->hasRole($roleOrPermission)) {
            return [
                'success' => false,
                'data' => [
                    'value' => false,
                    'message' => 'Role already assigned to user!',
                ],
                'status_code' => 409,
            ];
        }

        $user->assignRole($roleOrPermission);

        Log::info('Role assigned to user successfully', [
            'user_id' => $user->id,
            'role' => $roleOrPermission,
        ]);

        return [
            'success' => true,
            'data' => [
                'value' => true,
                'message' => 'Role assigned to user successfully!',
            ],
            'status_code' => 200,
        ];
    }

    /**
     * Assign permission to user internal method
     */
    private function assignPermissionToUserInternal(User $user, array $data): array
    {
        $validator = Validator::make($data, [
            'roleOrPermission' => 'required|string',
        ]);

        if ($validator->fails()) {
            return [
                'success' => false,
                'data' => [
                    'value' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors(),
                ],
                'status_code' => 400,
            ];
        }

        $roleOrPermission = $data['roleOrPermission'];

        if ($user->hasPermissionTo($roleOrPermission)) {
            return [
                'success' => false,
                'data' => [
                    'value' => false,
                    'message' => 'Permission already assigned to user!',
                ],
                'status_code' => 409,
            ];
        }

        $user->givePermissionTo($roleOrPermission);

        Log::info('Permission assigned to user successfully', [
            'user_id' => $user->id,
            'permission' => $roleOrPermission,
        ]);

        return [
            'success' => true,
            'data' => [
                'value' => true,
                'message' => 'Permission assigned to user successfully!',
            ],
            'status_code' => 200,
        ];
    }
}
