<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);

        $query = User::query()->with('role')->orderBy('id');

        if ($role = $request->string('role')->toString()) {
            $query->whereHas('role', fn ($q) => $q->where('code', $role));
        }

        return response()->json($query->paginate(50));
    }

    public function show(User $user): JsonResponse
    {
        $this->authorize('view', $user);

        return response()->json($user->load('role.permissions'));
    }
}
