<?php

namespace App\Http\Controllers;

use App\Http\Responses\ApiResponse;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * @OA\Tag(
 *     name="Auth",
 *     description="Authentication APIs"
 * )
 */
class AuthController extends Controller
{
    /**
     * @OA\Post(
     *     path="/register",
     *     tags={"Auth"},
     *     summary="Register a new user (admin only)",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","email","password"},
     *             @OA\Property(property="name", type="string"),
     *             @OA\Property(property="email", type="string"),
     *             @OA\Property(property="password", type="string")
     *         )
     *     ),
     *     @OA\Response(response=201, description="User created"),
     *     security={{"sanctum":{}}}
     * )
     */
    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        return ApiResponse::created($user, 'User registered successfully');
    }

    /**
     * @OA\Post(
     *     path="/login",
     *     tags={"Auth"},
     *     summary="Login and get token",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","password"},
     *             @OA\Property(property="email", type="string"),
     *             @OA\Property(property="password", type="string")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Token returned")
     * )
     */
    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            return ApiResponse::unauthorized(
                'The provided credentials are incorrect.'
            );
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return ApiResponse::success([
            'access_token' => $token,
            'token_type' => 'Bearer',
        ], 'Login successful');
    }

    /**
     * @OA\Post(
     *     path="/logout",
     *     tags={"Auth"},
     *     summary="Logout and revoke token",
     *     @OA\Response(response=200, description="Logged out"),
     *     security={{"sanctum":{}}}
     * )
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return ApiResponse::success(null, 'Logged out successfully');
    }
}
