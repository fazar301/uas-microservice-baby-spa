<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        try {
            Log::info('User registration attempt', [
                'email' => $request->email,
                'correlation_id' => $request->get('correlation_id'),
            ]);

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'noHP' => ['required','string','max:20','unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ],
        [
            'name.required' => 'Masukkan nama anda!',
            'noHP.required' => 'Masukkan Nomor HP anda!',
            'email.required' => 'Masukkan email anda!',
            'password.required' => 'Masukkan password anda!',
            'email.unique' => 'Email yang anda masukkan sudah terdaftar',
            'noHP.unique' => 'Nomor HP sudah terdaftar',
            'password.confirmed' => 'Password tidak sama'
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'noHP' => $request->noHP,
            'role' => 'customer',
            'password' => Hash::make($request->password),
                // 'email_verified_at' => now(), // Auto-verify email since SMTP is not available
        ]);

        event(new Registered($user));

        Auth::login($user);

            Log::info('User registered successfully', [
                'user_id' => $user->id,
                'email' => $user->email,
                'correlation_id' => $request->get('correlation_id'),
            ]);

        return redirect(route('dashboard', absolute: false));
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('User registration validation failed', [
                'email' => $request->email,
                'errors' => $e->errors(),
                'correlation_id' => $request->get('correlation_id'),
            ]);
            throw $e;
        } catch (\Exception $e) {
            Log::error('User registration failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'correlation_id' => $request->get('correlation_id'),
            ]);

            return redirect()->back()
                ->withInput($request->except('password', 'password_confirmation'))
                ->withErrors(['error' => 'Terjadi kesalahan saat registrasi. Silakan coba lagi.']);
        }
    }
}
