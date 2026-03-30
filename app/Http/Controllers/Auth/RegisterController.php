<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\VirtFusionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class RegisterController extends Controller
{
    public function show()
    {
        return view('auth.register');
    }

    public function store(Request $request, VirtFusionService $virtfusion)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'company' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'company' => $validated['company'] ?? null,
            'phone' => $validated['phone'] ?? null,
        ]);

        try {
            $vfUser = $virtfusion->createUser(
                $user->name,
                $user->email,
                $user->id
            );
            $user->update(['virtfusion_user_id' => $vfUser['data']['id'] ?? null]);
        } catch (\Exception $e) {
            \Log::error('VirtFusion user creation failed', ['error' => $e->getMessage()]);
        }

        Auth::login($user);

        return redirect()->route('dashboard')->with('success', 'Welkom bij Cloudito!');
    }
}
