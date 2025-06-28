<?php

namespace App\Modules\RolePermission\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\RolePermission\Services\RolePermissionService;
use App\Modules\RolePermission\Requests\CreateRoleAndPermissionRequest;
use App\Modules\RolePermission\Requests\AssignRoleToUserRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class RolePermissionController extends Controller
{
    protected $rolePermissionService;

    public function __construct(RolePermissionService $rolePermissionService)
    {
        $this->rolePermissionService = $rolePermissionService;
    }

    /**
     * Create role or permission, or assign permission to role
     *
     * @param CreateRoleAndPermissionRequest $request
     * @return JsonResponse
     */
    public function createRoleAndPermission(CreateRoleAndPermissionRequest $request): JsonResponse
    {
        try {
            $result = $this->rolePermissionService->createRoleAndPermission($request->validated());
            
            return response()->json($result['data'], $result['status_code']);
        } catch (\Exception $e) {
            Log::error('Error in createRoleAndPermission controller: ' . $e->getMessage(), [
                'exception' => $e->getTraceAsString()
            ]);

            return response()->json([
                'value' => false,
                'message' => 'Failed to process role/permission action'
            ], 500);
        }
    }

    /**
     * Assign role or permission to user
     *
     * @param AssignRoleToUserRequest $request
     * @return JsonResponse
     */
    public function assignRoleToUser(AssignRoleToUserRequest $request): JsonResponse
    {
        try {
            $result = $this->rolePermissionService->assignRoleToUser($request->validated());
            
            return response()->json($result['data'], $result['status_code']);
        } catch (\Exception $e) {
            Log::error('Error in assignRoleToUser controller: ' . $e->getMessage(), [
                'exception' => $e->getTraceAsString()
            ]);

            return response()->json([
                'value' => false,
                'message' => 'Failed to assign role/permission to user'
            ], 500);
        }
    }

    /**
     * Check role and permission for current user
     *
     * @return JsonResponse
     */
    public function checkRoleAndPermission(): JsonResponse
    {
        try {
            $result = $this->rolePermissionService->checkRoleAndPermission();
            
            return response()->json($result['data'], $result['status_code']);
        } catch (\Exception $e) {
            Log::error('Error in checkRoleAndPermission controller: ' . $e->getMessage(), [
                'exception' => $e->getTraceAsString()
            ]);

            return response()->json([
                'value' => false,
                'message' => 'Failed to check role and permission'
            ], 500);
        }
    }
}
