<?php

namespace App\Http\Controllers;

use App\Models\Classroom;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:100'],
            'email'    => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'role'     => ['required', Rule::in(['teacher', 'student'])],
            'class_id' => ['required_if:role,student', 'nullable', 'exists:classes,id'],
        ]);

        if ($data['role'] === 'teacher') {
            $data['class_id'] = null;
        }

        $user = User::create($data); // password di-hash otomatis oleh cast 'hashed'
        $token = $user->createToken('api')->plainTextToken;

        return response()->json(['data' => ['user' => $user, 'token' => $token]], 201);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            // Pesan sengaja generik agar tidak membocorkan apakah email terdaftar
            return response()->json(['message' => 'Email atau password salah.'], 401);
        }

        $token = $user->createToken('api')->plainTextToken;

        return response()->json(['data' => ['user' => $user, 'token' => $token]]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logout berhasil.']);
    }

    public function me(Request $request)
    {
        return response()->json(['data' => $request->user()]);
    }

    // Publik: dipakai form registrasi untuk memilih kelas
    public function classes()
    {
        return response()->json(['data' => Classroom::select('id', 'name')->orderBy('name')->get()]);
    }
}
