<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * Display a listing of users (Admin only).
     */
    public function index(Request $request): View
    {
        try {
            $user = $request->user();

            if (!$user->isAdmin()) {
                Log::warning('Unauthorized access attempt to user list', [
                    'user_id' => $user->id,
                    'correlation_id' => $request->get('correlation_id'),
                ]);

                abort(403, 'Unauthorized. Admin access required.');
            }

            $users = User::select('id', 'name', 'email', 'noHP', 'role', 'email_verified_at', 'created_at', 'updated_at')
                ->orderBy('created_at', 'desc')
                ->paginate(15);

            Log::info('User list retrieved', [
                'user_id' => $user->id,
                'total_users' => $users->total(),
                'correlation_id' => $request->get('correlation_id'),
            ]);

            return view('admin.users.index', [
                'users' => $users,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve user list', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'correlation_id' => $request->get('correlation_id'),
            ]);

            return redirect()->route('dashboard')
                ->with('error', 'Gagal memuat daftar pengguna.');
        }
    }

    /**
     * Display the specified user (Admin only).
     */
    public function show(Request $request, int $id): View
    {
        try {
            $user = $request->user();

            if (!$user->isAdmin()) {
                Log::warning('Unauthorized access attempt to user details', [
                    'user_id' => $user->id,
                    'target_user_id' => $id,
                    'correlation_id' => $request->get('correlation_id'),
                ]);

                abort(403, 'Unauthorized. Admin access required.');
            }

            $targetUser = User::select('id', 'name', 'email', 'noHP', 'role', 'email_verified_at', 'created_at', 'updated_at')
                ->find($id);

            if (!$targetUser) {
                Log::warning('User not found', [
                    'target_user_id' => $id,
                    'correlation_id' => $request->get('correlation_id'),
                ]);

                return redirect()->route('admin.users.index')
                    ->with('error', 'Pengguna tidak ditemukan.');
            }

            Log::info('User details retrieved', [
                'user_id' => $user->id,
                'target_user_id' => $id,
                'correlation_id' => $request->get('correlation_id'),
            ]);

            return view('admin.users.show', [
                'targetUser' => $targetUser,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to retrieve user details', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'correlation_id' => $request->get('correlation_id'),
            ]);

            return redirect()->route('admin.users.index')
                ->with('error', 'Gagal memuat detail pengguna.');
        }
    }

    /**
     * Show the form for editing the specified user (Admin only).
     */
    public function edit(Request $request, int $id): View
    {
        try {
            $user = $request->user();

            if (!$user->isAdmin()) {
                Log::warning('Unauthorized access attempt to edit user', [
                    'user_id' => $user->id,
                    'target_user_id' => $id,
                    'correlation_id' => $request->get('correlation_id'),
                ]);

                abort(403, 'Unauthorized. Admin access required.');
            }

            $targetUser = User::find($id);

            if (!$targetUser) {
                Log::warning('User not found for editing', [
                    'target_user_id' => $id,
                    'correlation_id' => $request->get('correlation_id'),
                ]);

                return redirect()->route('admin.users.index')
                    ->with('error', 'Pengguna tidak ditemukan.');
            }

            Log::info('User edit form accessed', [
                'user_id' => $user->id,
                'target_user_id' => $id,
                'correlation_id' => $request->get('correlation_id'),
            ]);

            return view('admin.users.edit', [
                'targetUser' => $targetUser,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to load user edit form', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'correlation_id' => $request->get('correlation_id'),
            ]);

            return redirect()->route('admin.users.index')
                ->with('error', 'Gagal memuat form edit pengguna.');
        }
    }

    /**
     * Update the specified user (Admin only).
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        try {
            $user = $request->user();

            if (!$user->isAdmin()) {
                Log::warning('Unauthorized access attempt to update user', [
                    'user_id' => $user->id,
                    'target_user_id' => $id,
                    'correlation_id' => $request->get('correlation_id'),
                ]);

                abort(403, 'Unauthorized. Admin access required.');
            }

            $targetUser = User::find($id);

            if (!$targetUser) {
                Log::warning('User not found for update', [
                    'target_user_id' => $id,
                    'correlation_id' => $request->get('correlation_id'),
                ]);

                return redirect()->route('admin.users.index')
                    ->with('error', 'Pengguna tidak ditemukan.');
            }

            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255', 'min:3'],
                'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $id],
                'noHP' => ['nullable', 'string', 'max:20', 'unique:users,noHP,' . $id],
                'role' => ['required', 'string', 'in:admin,customer'],
            ], [
                'name.required' => 'Nama wajib diisi.',
                'name.min' => 'Nama minimal 3 karakter.',
                'email.required' => 'Email wajib diisi.',
                'email.email' => 'Format email tidak valid.',
                'email.unique' => 'Email sudah terdaftar.',
                'noHP.unique' => 'Nomor HP sudah terdaftar.',
                'role.required' => 'Role wajib diisi.',
                'role.in' => 'Role harus admin atau customer.',
            ]);

            Log::info('User update attempt', [
                'user_id' => $user->id,
                'target_user_id' => $id,
                'correlation_id' => $request->get('correlation_id'),
            ]);

            $targetUser->update($validated);

            Log::info('User updated successfully', [
                'user_id' => $user->id,
                'target_user_id' => $id,
                'correlation_id' => $request->get('correlation_id'),
            ]);

            return redirect()->route('admin.users.index')
                ->with('success', 'Pengguna berhasil diperbarui.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('User update validation failed', [
                'user_id' => $user->id,
                'target_user_id' => $id,
                'errors' => $e->errors(),
                'correlation_id' => $request->get('correlation_id'),
            ]);

            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput();
        } catch (\Exception $e) {
            Log::error('Failed to update user', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'correlation_id' => $request->get('correlation_id'),
            ]);

            return redirect()->route('admin.users.index')
                ->with('error', 'Gagal memperbarui pengguna.');
        }
    }

    /**
     * Remove the specified user (Admin only).
     */
    public function destroy(Request $request, int $id): RedirectResponse
    {
        try {
            $user = $request->user();

            if (!$user->isAdmin()) {
                Log::warning('Unauthorized access attempt to delete user', [
                    'user_id' => $user->id,
                    'target_user_id' => $id,
                    'correlation_id' => $request->get('correlation_id'),
                ]);

                abort(403, 'Unauthorized. Admin access required.');
            }

            if ($user->id === $id) {
                Log::warning('Attempt to delete own account', [
                    'user_id' => $user->id,
                    'correlation_id' => $request->get('correlation_id'),
                ]);

                return redirect()->route('admin.users.index')
                    ->with('error', 'Anda tidak dapat menghapus akun sendiri.');
            }

            $targetUser = User::find($id);

            if (!$targetUser) {
                Log::warning('User not found for deletion', [
                    'target_user_id' => $id,
                    'correlation_id' => $request->get('correlation_id'),
                ]);

                return redirect()->route('admin.users.index')
                    ->with('error', 'Pengguna tidak ditemukan.');
            }

            Log::info('User deletion attempt', [
                'user_id' => $user->id,
                'target_user_id' => $id,
                'correlation_id' => $request->get('correlation_id'),
            ]);

            $targetUser->delete();

            Log::info('User deleted successfully', [
                'user_id' => $user->id,
                'deleted_user_id' => $id,
                'correlation_id' => $request->get('correlation_id'),
            ]);

            return redirect()->route('admin.users.index')
                ->with('success', 'Pengguna berhasil dihapus.');
        } catch (\Exception $e) {
            Log::error('Failed to delete user', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'correlation_id' => $request->get('correlation_id'),
            ]);

            return redirect()->route('admin.users.index')
                ->with('error', 'Gagal menghapus pengguna.');
        }
    }
}

