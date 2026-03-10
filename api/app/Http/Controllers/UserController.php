<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserCollection;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class UserController extends Controller
{

    public function index(Request $request)
    {
        $users = User::searchFor($request->search)
            ->filterByRole($request->role)
            ->latest()
            ->paginate();

        return response()->json(
            new UserCollection($users),
            Response::HTTP_OK);
    }

    public function updateRole(Request $request, User $user)
    {
        $request->validate([
            'role' => 'required|string|in:admin,editor,user',
        ]);
        if ($request->user()->role !== 'admin') {
            abort(403);
        }
        if ($user->assignRole($request->input('role'))) {
            return response()->json(['status' => 'success'], Response::HTTP_OK);
        }

        return response()->json(['status' => 'failed'], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}
