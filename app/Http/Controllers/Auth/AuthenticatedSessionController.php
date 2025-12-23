<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        try {
            Log::info('User login attempt', [
                'email' => $request->email,
                'correlation_id' => $request->get('correlation_id'),
            ]);

        $request->authenticate();

        $request->session()->regenerate();

            $user = Auth::user();

            if($user['email_verified_at'] == null){
                return redirect('/verify-email');
            }
            Log::info('User logged in successfully', [
                'user_id' => $user->id,
                'email' => $user->email,
                'correlation_id' => $request->get('correlation_id'),
            ]);

        if ($request->session()->has('intended')) {
            $intendedUrl = $request->session()->get('intended');
            $request->session()->forget('intended');
                Log::info('Redirect to intended URL after login', [
                    'user_id' => $user->id,
                    'url' => $intendedUrl,
                    'correlation_id' => $request->get('correlation_id'),
                ]);
            return redirect()->to($intendedUrl);
        }

            // Redirect admin to admin panel, customer to dashboard
            if ($user->isAdmin()) {
                Log::info('Admin user redirected to admin panel', [
                    'user_id' => $user->id,
                    'correlation_id' => $request->get('correlation_id'),
                ]);
                return redirect('/admin');
            }

        return redirect()->route('dashboard');
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Login failed: Invalid credentials', [
                'email' => $request->email,
                'correlation_id' => $request->get('correlation_id'),
            ]);
            throw $e;
        } catch (\Exception $e) {
            Log::error('Login failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'correlation_id' => $request->get('correlation_id'),
            ]);

            return redirect()->back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Terjadi kesalahan saat login. Silakan coba lagi.']);
        }
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        try {
            $user = Auth::user();

            Log::info('User logout attempt', [
                'user_id' => $user->id ?? null,
                'correlation_id' => $request->get('correlation_id'),
            ]);

        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

            Log::info('User logged out successfully', [
                'user_id' => $user->id ?? null,
                'correlation_id' => $request->get('correlation_id'),
            ]);

            return redirect('/');
        } catch (\Exception $e) {
            Log::error('Logout failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'correlation_id' => $request->get('correlation_id'),
            ]);

        return redirect('/');
        }
    }
}
