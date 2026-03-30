<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;

class ForgotPasswordController extends Controller
{
    public function show()
    {
        return view('auth.forgot-password');
    }

    public function store(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        return $status === Password::RESET_LINK_SENT
            ? back()->with('status', 'We hebben je een wachtwoord reset link gestuurd!')
            : back()->withErrors(['email' => $this->translateStatus($status)]);
    }

    private function translateStatus(string $status): string
    {
        return match ($status) {
            Password::INVALID_USER => 'Er is geen account met dit e-mailadres.',
            Password::RESET_THROTTLED => 'Even geduld, je kunt zo opnieuw proberen.',
            default => 'Er is iets misgegaan. Probeer het opnieuw.',
        };
    }
}
