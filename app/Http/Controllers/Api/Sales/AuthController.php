<?php

namespace App\Http\Controllers\Api\Sales;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

/**
 * Login khusus Sales APK. Terpisah dari login web admin supaya token Sanctum
 * yang dihasilkan bisa diberi "ability" khusus (mis. 'sales-app') di masa depan.
 */
class AuthController extends Controller
{
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ], 422);
        }

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'success' => false,
                'message' => 'Email atau password salah.',
            ], 401);
        }

        /** @var User $user */
        $user = Auth::user();

        if ($user->role !== 'sales') {
            Auth::logout();
            return response()->json([
                'success' => false,
                'message' => 'Akun ini bukan akun Sales.',
            ], 403);
        }

        $token = $user->createToken('sales-app')->plainTextToken;

        return response()->json([
            'success' => true,
            'token' => $token,
            'sales_id' => $user->id,
            'name' => $user->name,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['success' => true]);
    }
}
