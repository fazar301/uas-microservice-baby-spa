<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        try {
            $user = $request->user();

            Log::info('User profile page accessed', [
                'user_id' => $user->id,
                'correlation_id' => $request->get('correlation_id'),
            ]);

        return view('dashboard_user.setting', [
                'user' => $user,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to load user profile page', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'correlation_id' => $request->get('correlation_id'),
        ]);

            return redirect()->route('dashboard')
                ->with('error', 'Gagal memuat halaman profil.');
        }
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        try {
            $user = $request->user();

            Log::info('User profile update attempt', [
                'user_id' => $user->id,
                'correlation_id' => $request->get('correlation_id'),
            ]);

            $user->fill($request->validated());

            if ($user->isDirty('email')) {
                $user->email_verified_at = null;
                Log::info('Email changed, verification reset', [
                    'user_id' => $user->id,
                    'new_email' => $user->email,
                    'correlation_id' => $request->get('correlation_id'),
                ]);
            }

            $user->save();

            Log::info('User profile updated successfully', [
                'user_id' => $user->id,
                'correlation_id' => $request->get('correlation_id'),
            ]);

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
        } catch (\Exception $e) {
            Log::error('Failed to update user profile', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'correlation_id' => $request->get('correlation_id'),
            ]);

            return Redirect::route('profile.edit')
                ->with('error', 'Gagal memperbarui profil.');
        }
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        try {
            $user = $request->user();

        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

            Log::info('User account deletion attempt', [
                'user_id' => $user->id,
                'correlation_id' => $request->get('correlation_id'),
            ]);

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

            Log::info('User account deleted successfully', [
                'deleted_user_id' => $user->id,
                'correlation_id' => $request->get('correlation_id'),
            ]);

        return Redirect::to('/');
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('User account deletion validation failed', [
                'user_id' => $user->id,
                'errors' => $e->errors(),
                'correlation_id' => $request->get('correlation_id'),
            ]);

            return redirect()->back()
                ->withErrors($e->errors(), 'userDeletion');
        } catch (\Exception $e) {
            Log::error('Failed to delete user account', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'correlation_id' => $request->get('correlation_id'),
            ]);

            return Redirect::route('profile.edit')
                ->with('error', 'Gagal menghapus akun.');
        }
    }
}
