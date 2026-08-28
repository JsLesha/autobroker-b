<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()?->hasPermission('users.read'), 403);

        return response()->json(Role::query()->with('permissions')->orderBy('id')->get());
    }

    public function permissions(Request $request): JsonResponse
    {
        abort_unless($request->user()?->hasPermission('users.read'), 403);

        return response()->json(Permission::query()->orderBy('group_name')->orderBy('code')->get());
    }

    public function syncPermissions(Request $request, Role $role): JsonResponse
    {
        abort_unless($request->user()?->hasPermission('users.update'), 403);

        $data = $request->validate([
            'permission_ids' => ['required', 'array'],
            'permission_ids.*' => ['exists:permissions,id'],
        ]);
        $role->permissions()->sync($data['permission_ids']);

        return response()->json($role->load('permissions'));
    }
}
