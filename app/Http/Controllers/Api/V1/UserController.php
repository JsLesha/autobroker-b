<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $query = User::query()->with('role')->orderBy('id');

        if ($role = $request->string('role')->toString()) {
            $query->whereHas('role', fn ($q) => $q->where('code', $role));
        }
        if ($q = $request->string('q')->toString()) {
            $query->where(function ($inner) use ($q) {
                $inner->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('nickname', 'like', "%{$q}%");
            });
        }
        if ($request->boolean('archived')) {
            $query->where('active', false);
        } else {
            $query->where('active', true);
        }

        return response()->json($query->paginate($request->integer('limit') ?: 50));
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', User::class);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role_id' => ['required', 'exists:roles,id'],
            'nickname' => ['nullable', 'string', 'max:191'],
            'active' => ['sometimes', 'boolean'],
            'active_prebid' => ['sometimes', 'boolean'],
        ]);
        $data['active'] = $data['active'] ?? true;
        $data['public_offer_status'] = 'pending';

        return response()->json(User::query()->create($data)->load('role'), 201);
    }

    public function show(User $user): JsonResponse
    {
        $this->authorize('view', $user);

        return response()->json($user->load('role.permissions', 'extraPermissions'));
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $this->authorize('update', $user);

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:191'],
            'email' => ['sometimes', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'string', 'min:8'],
            'role_id' => ['sometimes', 'exists:roles,id'],
            'nickname' => ['nullable', 'string', 'max:191'],
            'active' => ['sometimes', 'boolean'],
            'active_prebid' => ['sometimes', 'boolean'],
        ]);
        if (empty($data['password'])) {
            unset($data['password']);
        }
        $user->update($data);

        return response()->json($user->fresh('role'));
    }
}
