<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Modules\RolePermission\Controllers\RolePermissionController as ModuleRolePermissionController;
use Illuminate\Http\Request;

class RolePermissionController extends Controller
{
    protected $rolePermissionController;

    public function __construct(ModuleRolePermissionController $rolePermissionController)
    {
        $this->rolePermissionController = $rolePermissionController;
    }

    public function createRoleAndPermission(Request $request)
    {
        return $this->rolePermissionController->createRoleAndPermission($request);
    }

    public function assignRoleToUser(Request $request)
    {
        return $this->rolePermissionController->assignRoleToUser($request);
    }

    public function checkRoleAndPermission(Request $request)
    {
        return $this->rolePermissionController->checkRoleAndPermission($request);
    }
}
