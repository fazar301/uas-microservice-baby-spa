<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\RegisterRequest;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Requests\Api\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Register a new user
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            Log::info('User registration attempt', [
                'email' => $request->email,
                'correlation_id' => $request->get('correlation_id'),
            ]);

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'noHP' => $request->noHP,
                'role' => 'customer',
                'email_verified_at' => now(), // Auto-verify email since SMTP is not available
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;

            Log::info('User registered successfully', [
                'user_id' => $user->id,
                'email' => $user->email,
                'correlation_id' => $request->get('correlation_id'),
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'User registered successfully',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'noHP' => $user->noHP,
                        'role' => $user->role,
                    ],
                    'token' => $token,
                    'token_type' => 'Bearer',
                ],
            ], 201);
        } catch (\Exception $e) {
            Log::error('User registration failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'correlation_id' => $request->get('correlation_id'),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Registration failed. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Login user
     */
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            Log::info('User login attempt', [
                'email' => $request->email,
                'correlation_id' => $request->get('correlation_id'),
            ]);

            $user = User::where('email', $request->email)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                Log::warning('Login failed: Invalid credentials', [
                    'email' => $request->email,
                    'correlation_id' => $request->get('correlation_id'),
                ]);

                throw ValidationException::withMessages([
                    'email' => ['The provided credentials are incorrect.'],
                ]);
            }

            // Revoke all existing tokens
            $user->tokens()->delete();

            // Create new token
            $token = $user->createToken('auth_token')->plainTextToken;

            Log::info('User logged in successfully', [
                'user_id' => $user->id,
                'email' => $user->email,
                'correlation_id' => $request->get('correlation_id'),
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Login successful',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'noHP' => $user->noHP,
                        'role' => $user->role,
                    ],
                    'token' => $token,
                    'token_type' => 'Bearer',
                ],
            ], 200);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Login failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'correlation_id' => $request->get('correlation_id'),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Login failed. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Get authenticated user profile
     */
    public function profile(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            Log::info('User profile retrieved', [
                'user_id' => $user->id,
                'correlation_id' => $request->get('correlation_id'),
            ]);

            return response()->json([
                'status' => 'success',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'noHP' => $user->noHP,
                        'role' => $user->role,
                        'email_verified_at' => $user->email_verified_at,
                        'created_at' => $user->created_at,
                        'updated_at' => $user->updated_at,
                    ],
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve user profile', [
                'error' => $e->getMessage(),
                'correlation_id' => $request->get('correlation_id'),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve profile.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Update user profile
     */
    public function update(UpdateUserRequest $request): JsonResponse
    {
        try {
            $user = $request->user();

            Log::info('User profile update attempt', [
                'user_id' => $user->id,
                'correlation_id' => $request->get('correlation_id'),
            ]);

            $updateData = [];
            if ($request->filled('name')) {
                $updateData['name'] = $request->name;
            }
            if ($request->filled('email')) {
                $updateData['email'] = $request->email;
            }
            if ($request->filled('noHP')) {
                $updateData['noHP'] = $request->noHP;
            }
            if ($request->filled('password')) {
                $updateData['password'] = Hash::make($request->password);
            }

            $user->update($updateData);

            Log::info('User profile updated successfully', [
                'user_id' => $user->id,
                'correlation_id' => $request->get('correlation_id'),
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Profile updated successfully',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'noHP' => $user->noHP,
                        'role' => $user->role,
                        'updated_at' => $user->updated_at,
                    ],
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Failed to update user profile', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'correlation_id' => $request->get('correlation_id'),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update profile.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Logout user
     */
    public function logout(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            Log::info('User logout attempt', [
                'user_id' => $user->id,
                'correlation_id' => $request->get('correlation_id'),
            ]);

            // Revoke current token
            $request->user()->currentAccessToken()->delete();

            Log::info('User logged out successfully', [
                'user_id' => $user->id,
                'correlation_id' => $request->get('correlation_id'),
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Logged out successfully',
            ], 200);
        } catch (\Exception $e) {
            Log::error('Logout failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $request->get('correlation_id'),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Logout failed.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Get all users (Admin only)
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if ($user->role !== 'admin') {
                Log::warning('Unauthorized access attempt to user list', [
                    'user_id' => $user->id,
                    'correlation_id' => $request->get('correlation_id'),
                ]);

                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized. Admin access required.',
                ], 403);
            }

            $users = User::select('id', 'name', 'email', 'noHP', 'role', 'created_at', 'updated_at')
                ->paginate(15);

            Log::info('User list retrieved', [
                'user_id' => $user->id,
                'total_users' => $users->total(),
                'correlation_id' => $request->get('correlation_id'),
            ]);

            return response()->json([
                'status' => 'success',
                'data' => $users,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve user list', [
                'error' => $e->getMessage(),
                'correlation_id' => $request->get('correlation_id'),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve users.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Get specific user by ID (Admin only)
     */
    public function show(Request $request, int $id): JsonResponse
    {
        try {
            $user = $request->user();

            if ($user->role !== 'admin') {
                Log::warning('Unauthorized access attempt to user details', [
                    'user_id' => $user->id,
                    'target_user_id' => $id,
                    'correlation_id' => $request->get('correlation_id'),
                ]);

                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized. Admin access required.',
                ], 403);
            }

            $targetUser = User::select('id', 'name', 'email', 'noHP', 'role', 'email_verified_at', 'created_at', 'updated_at')
                ->find($id);

            if (!$targetUser) {
                Log::warning('User not found', [
                    'target_user_id' => $id,
                    'correlation_id' => $request->get('correlation_id'),
                ]);

                return response()->json([
                    'status' => 'error',
                    'message' => 'User not found.',
                ], 404);
            }

            Log::info('User details retrieved', [
                'user_id' => $user->id,
                'target_user_id' => $id,
                'correlation_id' => $request->get('correlation_id'),
            ]);

            return response()->json([
                'status' => 'success',
                'data' => [
                    'user' => $targetUser,
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve user details', [
                'error' => $e->getMessage(),
                'correlation_id' => $request->get('correlation_id'),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve user details.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }

    /**
     * Delete user (Admin only)
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        try {
            $user = $request->user();

            if ($user->role !== 'admin') {
                Log::warning('Unauthorized access attempt to delete user', [
                    'user_id' => $user->id,
                    'target_user_id' => $id,
                    'correlation_id' => $request->get('correlation_id'),
                ]);

                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized. Admin access required.',
                ], 403);
            }

            if ($user->id === $id) {
                Log::warning('Attempt to delete own account', [
                    'user_id' => $user->id,
                    'correlation_id' => $request->get('correlation_id'),
                ]);

                return response()->json([
                    'status' => 'error',
                    'message' => 'You cannot delete your own account.',
                ], 400);
            }

            $targetUser = User::find($id);

            if (!$targetUser) {
                Log::warning('User not found for deletion', [
                    'target_user_id' => $id,
                    'correlation_id' => $request->get('correlation_id'),
                ]);

                return response()->json([
                    'status' => 'error',
                    'message' => 'User not found.',
                ], 404);
            }

            $targetUser->delete();

            Log::info('User deleted successfully', [
                'user_id' => $user->id,
                'deleted_user_id' => $id,
                'correlation_id' => $request->get('correlation_id'),
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'User deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            Log::error('Failed to delete user', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'correlation_id' => $request->get('correlation_id'),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to delete user.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error',
            ], 500);
        }
    }
}


