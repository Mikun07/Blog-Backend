<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function register(RegisterRequest $request)
    {
        $user = User::create([
            'name' => $request->validated('name'),
            'username' => $request->validated('username'),
            'email' => $request->validated('email'),
            'password' => Hash::make($request->validated('password')),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Registration successful.',
            'data' => [
                'user' => $user,
                'token' => $user->createToken('api-token')->plainTextToken,
            ],
        ], 201);
    }

    public function login(LoginRequest $request)
    {
        $user = User::where('email', $request->validated('email'))->first();

        if (! $user || ! Hash::check($request->validated('password'), $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Email or password is invalid.',
                'data' => null,
            ], 401);
        }

        return response()->json([
            'success' => true,
            'message' => 'Login successful.',
            'data' => [
                'user' => $user,
                'token' => $user->createToken('api-token')->plainTextToken,
            ],
        ]);
    }

    public function logout(Request $request)
    {
        if ($request->user()?->currentAccessToken()) {
            $request->user()->currentAccessToken()->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Logout successful.',
            'data' => null,
        ]);
    }

    public function getUser(Request $request)
    {
        return response()->json([
            'success' => true,
            'message' => 'User retrieved.',
            'data' => $request->user(),
        ]);
    }
}
