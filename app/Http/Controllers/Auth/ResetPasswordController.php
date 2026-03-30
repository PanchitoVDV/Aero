<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class ResetPasswordController extends Controller
{
    public function show(Request $request, string $token)
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email', ''),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'min:8', 'confirmed'],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('status', 'Je wachtwoord is gewijzigd! Je kunt nu inloggen.')
            : back()->withErrors(['email' => $this->translateStatus($status)]);
    }

    private function translateStatus(string $status): string
    {
        return match ($status) {
            Password::INVALID_USER => 'Er is geen account met dit e-mailadres.',
            Password::INVALID_TOKEN => 'Deze reset link is ongeldig of verlopen.',
            Password::RESET_THROTTLED => 'Even geduld, probeer het zo opnieuw.',
            default => 'Er is iets misgegaan.',
        };
    }
}
