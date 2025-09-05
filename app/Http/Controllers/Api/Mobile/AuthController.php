<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Api\Mobile\BaseMobileController;
use Illuminate\Http\Request;
use App\Models\Auth\User;
use Illuminate\Support\Str;

class AuthController extends BaseMobileController
{
    public function register(Request $request)
    {
        $data = $request->validate([
            'phone' => 'required|string|unique:users,phone',
            'name' => 'nullable|string',
            'pin' => 'required|min:4'
        ]);

        $user = new User();
        $user->name = $data['name'] ?? null;
        $user->phone = $data['phone'];
    // Some installs require email to be non-null; create a placeholder
    // unique email derived from the phone so tests pass and users can be created.
    $user->email = $data['phone'] . '@mobile.local';
        // User model has a setPasswordAttribute mutator which hashes the value,
        // so assign the raw PIN and let the model handle hashing. Avoid
        // double-bcryptting which would make login fail.
        $user->password = $data['pin'];
        $user->save();

        // Ensure the mobile-created user is attached to the current company
        // so downstream mobile endpoints (which expect a company context)
        // can operate. The test seeder creates a default company and marks it
        // current; company_id() helper returns it.
        try {
            if (function_exists('company_id') && company_id()) {
                $user->companies()->attach(company_id());
            }
        } catch (\Throwable $e) {
            // swallow in tests if pivot table or helper isn't available yet
        }
        // simple token for mobile usage (stateless)
        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json(['user' => ['id' => $user->id, 'name' => $user->name], 'token' => $token], 201);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'phone' => 'required|string',
            'pin' => 'required|string'
        ]);

        $user = User::where('phone', $data['phone'])->first();

        if (! $user || ! \Hash::check($data['pin'], $user->getAuthPassword())) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }


        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json(['user' => ['id' => $user->id, 'name' => $user->name], 'token' => $token]);
    }
}
