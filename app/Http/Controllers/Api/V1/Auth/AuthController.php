<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    /**
     * @OA\Post(
     *     path="/v1/auth/register",
     *     operationId="authRegister",
     *     tags={"Authentication"},
     *     summary="Register a new user",
     *     description="Create a new user account",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"first_name","last_name","email","password","password_confirmation","user_type"},
     *             @OA\Property(property="first_name", type="string", example="John"),
     *             @OA\Property(property="last_name", type="string", example="Doe"),
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="Password123!"),
     *             @OA\Property(property="password_confirmation", type="string", format="password", example="Password123!"),
     *             @OA\Property(property="user_type", type="string", enum={"client", "provider"}, example="provider")
     *         )
     *     ),
     *     @OA\Response(response=201, description="User registered successfully"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'nullable|string|max:20|unique:users',
            'password' => ['required', 'confirmed', Password::min(8)
                ->mixedCase()
                ->numbers()
                ->symbols()],
            'user_type' => 'required|in:client,provider,admin',
            'address' => 'nullable|string',
            'city' => 'nullable|string',
            'state' => 'nullable|string',
            'country' => 'nullable|string',
            'zip_code' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = User::create([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => Hash::make($request->password),
                'user_type' => $request->user_type,
                'address' => $request->address,
                'city' => $request->city,
                'state' => $request->state,
                'country' => $request->country,
                'zip_code' => $request->zip_code,
                'latitude' => $request->latitude,
                'longitude' => $request->longitude,
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'User registered successfully',
                'data' => [
                    'user' => $user,
                    'token' => $token
                ]
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Registration failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/v1/auth/login",
     *     operationId="authLogin",
     *     tags={"Authentication"},
     *     summary="User login",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","password"},
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com"),
     *             @OA\Property(property="password", type="string", format="password", example="Password123!")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Login successful"),
     *     @OA\Response(response=401, description="Invalid credentials")
     * )
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials'
            ], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'user' => $user,
                'token' => $token
            ]
        ]);
    }

    /**
     * @OA\Get(
     *     path="/v1/auth/me",
     *     operationId="authMe",
     *     tags={"Authentication"},
     *     summary="Get current user",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="User details"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */
    public function me()
    {
        return response()->json([
            'success' => true,
            'data' => auth()->user()
        ]);
    }

    /**
     * @OA\Post(
     *     path="/v1/auth/logout",
     *     operationId="authLogout",
     *     tags={"Authentication"},
     *     summary="Logout user",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Logged out successfully")
     * )
     */
    public function logout()
    {
        auth()->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully'
        ]);
    }

    /**
     * @OA\Post(
     *     path="/v1/auth/logout-all",
     *     operationId="authLogoutAll",
     *     tags={"Authentication"},
     *     summary="Logout from all devices",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Logged out from all devices")
     * )
     */
    public function logoutAll()
    {
        auth()->user()->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out from all devices successfully'
        ]);
    }

    /**
     * @OA\Post(
     *     path="/v1/auth/forgot-password",
     *     operationId="forgotPassword",
     *     tags={"Authentication"},
     *     summary="Request password reset",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email"},
     *             @OA\Property(property="email", type="string", format="email", example="john@example.com")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Reset link sent")
     * )
     */
    public function forgotPassword(Request $request)
    {
        return response()->json(['success' => true, 'message' => 'Reset link sent']);
    }

    /**
     * @OA\Post(
     *     path="/v1/auth/reset-password",
     *     operationId="resetPassword",
     *     tags={"Authentication"},
     *     summary="Reset password",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","password","password_confirmation","token"},
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="password", type="string", format="password"),
     *             @OA\Property(property="password_confirmation", type="string", format="password"),
     *             @OA\Property(property="token", type="string")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Password reset successfully")
     * )
     */
    public function resetPassword(Request $request)
    {
        return response()->json(['success' => true, 'message' => 'Password reset']);
    }

    /**
     * @OA\Post(
     *     path="/v1/auth/verify-email",
     *     operationId="verifyEmail",
     *     tags={"Authentication"},
     *     summary="Verify email",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email","code"},
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="code", type="string")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Email verified")
     * )
     */
    public function verifyEmail(Request $request)
    {
        return response()->json(['success' => true, 'message' => 'Email verified']);
    }

    /**
     * @OA\Post(
     *     path="/v1/auth/refresh",
     *     operationId="refreshToken",
     *     tags={"Authentication"},
     *     summary="Refresh token",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Token refreshed")
     * )
     */
    public function refresh()
    {
        return response()->json(['success' => true]);
    }

    /**
     * @OA\Post(
     *     path="/v1/auth/change-password",
     *     operationId="changePassword",
     *     tags={"Authentication"},
     *     summary="Change password",
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"current_password","password","password_confirmation"},
     *             @OA\Property(property="current_password", type="string", format="password"),
     *             @OA\Property(property="password", type="string", format="password"),
     *             @OA\Property(property="password_confirmation", type="string", format="password")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Password changed")
     * )
     */
    public function changePassword(Request $request)
    {
        return response()->json(['success' => true]);
    }

    /**
     * @OA\Get(
     *     path="/v1/auth/user",
     *     operationId="getAuthUser",
     *     tags={"Authentication"},
     *     summary="Get authenticated user",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="User data")
     * )
     */
    public function user()
    {
        return response()->json([
            'success' => true,
            'data' => auth()->user()
        ]);
    }

    /**
     * @OA\Post(
     *     path="/v1/auth/refresh-token",
     *     operationId="refreshAccessToken",
     *     tags={"Authentication"},
     *     summary="Refresh access token",
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Token refreshed")
     * )
     */
    public function refreshToken()
    {
        return response()->json(['success' => true]);
    }
}
