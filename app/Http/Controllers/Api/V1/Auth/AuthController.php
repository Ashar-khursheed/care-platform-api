<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use OpenApi\Attributes as OA;
use Illuminate\Support\Facades\Auth;


class AuthController extends Controller
{
    #[OA\Post(
        path: '/api/v1/auth/register',
        summary: 'User Registration',
        description: 'Register a new user (client or provider)',
        operationId: 'authRegister',
        tags: ['Authentication']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['first_name', 'last_name', 'email', 'password', 'password_confirmation', 'user_type'],
            properties: [
                new OA\Property(property: 'first_name', type: 'string', example: 'John'),
                new OA\Property(property: 'last_name', type: 'string', example: 'Doe'),
                new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john@example.com'),
                new OA\Property(property: 'password', type: 'string', format: 'password', minLength: 8, example: 'Password123!'),
                new OA\Property(property: 'password_confirmation', type: 'string', format: 'password', example: 'Password123!'),
                new OA\Property(property: 'user_type', type: 'string', enum: ['client', 'provider'], example: 'client'),
                new OA\Property(property: 'phone_number', type: 'string', example: '+1234567890')
            ]
        )
    )]
    #[OA\Response(response: 201, description: 'User registered successfully')]
    #[OA\Response(response: 422, description: 'Validation error')]
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'user_type' => 'required|in:client,provider',
            'phone_number' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'user_type' => $request->user_type,
            'phone_number' => $request->phone_number,
        ]);

        // Generate token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'User registered successfully. Please verify your email.',
            'data' => [
                'user' => $user,
                'access_token' => $token,
                'token_type' => 'Bearer',
            ]
        ], 201);
    }

    #[OA\Post(
        path: '/api/v1/auth/login',
        summary: 'User Login',
        tags: ['Authentication']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['email', 'password'],
            properties: [
                new OA\Property(property: 'email', type: 'string', format: 'email'),
                new OA\Property(property: 'password', type: 'string', format: 'password')
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Login success')]
    #[OA\Response(response: 401, description: 'Invalid credentials')]
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid login details'
            ], 401);
        }

        $user = User::where('email', $request['email'])->firstOrFail();
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $user,
                'access_token' => $token,
                'token_type' => 'Bearer'
            ]
        ]);
    }

    #[OA\Get(
        path: '/api/v1/auth/me',
        summary: 'Get current user',
        security: [['bearerAuth' => []]],
        tags: ['Authentication']
    )]
    #[OA\Response(response: 200, description: 'Success')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    public function me(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => $request->user()
        ]);
    }

    #[OA\Post(
        path: '/api/v1/auth/logout',
        summary: 'User Logout',
        security: [['bearerAuth' => []]],
        tags: ['Authentication']
    )]
    #[OA\Response(response: 200, description: 'Logout success')]
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully'
        ]);
    }

    #[OA\Post(
        path: '/api/v1/auth/logout-all',
        summary: 'Logout from all devices',
        security: [['bearerAuth' => []]],
        tags: ['Authentication']
    )]
    #[OA\Response(response: 200, description: 'Success')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    public function logoutAll()
    {
        auth()->user()->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out from all devices successfully'
        ]);
    }

    #[OA\Post(
        path: '/api/v1/auth/forgot-password',
        summary: 'Forgot password',
        tags: ['Authentication']
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['email'],
            properties: [
                new OA\Property(property: 'email', type: 'string', format: 'email')
            ]
        )
    )]
    #[OA\Response(response: 200, description: 'Reset link sent')]
    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        
        // Handle forgot password...
        
        return response()->json([
            'success' => true,
            'message' => 'Password reset link sent'
        ]);
    }

    #[OA\Post(
        path: '/api/v1/auth/reset-password',
        summary: 'Reset password',
        tags: ['Authentication']
    )]
    #[OA\Response(response: 200, description: 'Password reset')]
    public function resetPassword(Request $request)
    {
        return response()->json(['success' => true, 'message' => 'Password reset']);
    }

    #[OA\Post(
        path: '/api/v1/auth/verify-email',
        summary: 'Verify email address',
        tags: ['Authentication']
    )]
    #[OA\Response(response: 200, description: 'Success')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    public function verifyEmail(Request $request)
    {
        return response()->json(['success' => true, 'message' => 'Email verified']);
    }

    #[OA\Post(
        path: '/api/v1/auth/refresh',
        summary: 'Refresh access token',
        security: [['bearerAuth' => []]],
        tags: ['Authentication']
    )]
    #[OA\Response(response: 200, description: 'Success')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    public function refresh()
    {
        return response()->json(['success' => true]);
    }

    #[OA\Post(
        path: '/api/v1/auth/change-password',
        summary: 'Change password',
        security: [['bearerAuth' => []]],
        tags: ['Authentication']
    )]
    #[OA\Response(response: 200, description: 'Success')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    public function changePassword(Request $request)
    {
        return response()->json(['success' => true]);
    }

    #[OA\Get(
        path: '/api/v1/auth/user',
        summary: 'Get user details',
        security: [['bearerAuth' => []]],
        tags: ['Authentication']
    )]
    #[OA\Response(response: 200, description: 'Success')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    public function user()
    {
        return response()->json([
            'success' => true,
            'data' => auth()->user()
        ]);
    }

    #[OA\Post(
        path: '/api/v1/auth/refresh-token',
        summary: 'Refresh token',
        security: [['bearerAuth' => []]],
        tags: ['Authentication']
    )]
    #[OA\Response(response: 200, description: 'Success')]
    #[OA\Response(response: 401, description: 'Unauthenticated')]
    public function refreshToken()
    {
        return response()->json(['success' => true]);
    }
}
