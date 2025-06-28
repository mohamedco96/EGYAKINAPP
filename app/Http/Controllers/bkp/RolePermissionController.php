<?php

namespace App\Http\Controllers;

use App\Models\RolePermission;
use App\Http\Requests\StoreRolePermissionRequest;
use App\Http\Requests\UpdateRolePermissionRequest;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Validator;
use App\Models\User;

class RolePermissionController extends Controller
{
    public function createRoleAndPermission(Request $request)
    {
        $action = $request->input('action');

        switch ($action) {
            case 'create_role':
                $roleName = $request->input('role');
                $role = Role::create(['name' => $roleName]);
                return "Role created successfully!";
                break;

            case 'create_permission':
                $permissionName = $request->input('permission');
                $permission = Permission::create(['name' => $permissionName]);
                return "Permission created successfully!";
                break;

            case 'assign_permission':
                // Validate the request data
                $validator = Validator::make($request->all(), [
                    'role' => 'required|string',
                    'permission' => 'required|string',
                ]);

                // Check if validation fails
                if ($validator->fails()) {
                    return response()->json(['error' => $validator->errors()], 400);
                }

                $roleName = $request->input('role');
                $permissionName = $request->input('permission');

                $role = Role::findByName($roleName);
                $permission = Permission::findByName($permissionName);

                // Check if role and permission exist
                if (!$role || !$permission) {
                    return response()->json(['error' => 'Role or Permission not found!'], 404);
                }

                // Assign permission to role
                $role->givePermissionTo($permission);
                return "Permission assigned to role successfully!";
                break;

            case 'remove_role':

                break;

            case 'revoke_permission':
                //$role->revokePermissionTo($permission);
                break;

            default:
                return "Invalid action!";
        }
    }

    public function assignRoleToUser(Request $request)
    {
        // Find a user
        $doctorId = auth()->user()->id;
        $user = User::find($doctorId);
        $action = $request->input('action');

        switch ($action) {
            case 'assign_role':
                // Validate the request data
                $validator = Validator::make($request->all(), [
                    'roleOrPermission' => 'required|string',
                ]);

                // Check if validation fails
                if ($validator->fails()) {
                    return response()->json(['error' => $validator->errors()], 400);
                }

                $roleOrPermission = $request->input('roleOrPermission');
                // Assign the role to the user
                $user->assignRole($roleOrPermission);

                $response = [
                    'value' => true,
                    'message' => 'Role assigned to user successfully!',
                ];
                return response()->json($response, 200);
                break;


            case 'assign_permission':
                // Validate the request data
                $validator = Validator::make($request->all(), [
                    'roleOrPermission' => 'required|string',
                ]);

                // Check if validation fails
                if ($validator->fails()) {
                    return response()->json(['error' => $validator->errors()], 400);
                }

                $roleOrPermission = $request->input('roleOrPermission');
                // Assign the role to the user
                $user->givePermissionTo($roleOrPermission);

                $response = [
                    'value' => true,
                    'message' => 'Permission assigned to user successfully!',
                ];
                return response()->json($response, 200);

            default:
                return "Invalid action!";
        }
    }

    public function checkRoleAndPermission()
    {
        // Find the currently logged in user
        $user = auth()->user();

        //$role = Role::findByName('admin'); // Retrieve the role by its name
        //$permission = Permission::findByName('create post'); // Retrieve the permission by its name

        if ($user->hasRole('admin')) {
            return response()->json('user have admin role', 200);
        } else {
            echo 'user not have admin role' . "\n";
        }

        // Check if the user has permission to edit articles
        if ($user->hasPermissionTo('delete patient', 'web')) {
            return response()->json('User has permission to delete patient ', 200);
        } else {
            return response()->json('User does not have permission to edit articles', 200);
        }
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRolePermissionRequest $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(RolePermission $rolePermission)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(RolePermission $rolePermission)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRolePermissionRequest $request, RolePermission $rolePermission)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(RolePermission $rolePermission)
    {
        //
    }
}
