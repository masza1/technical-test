<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules\Password as RulesPassword;

class ResetPasswordController extends Controller
{
    public function createLinkResetPassword(Request $request)
    {
        if (auth('api')->check()) {
            return response()->json([
                'message' => 'Reset Password token was successfully created.',
                'token' => Password::createToken(auth()->user()),
            ]);
        }

        $validator = validator()->make($request->only('email'), [
            'email' => ['required', 'email'],
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = User::where('email', $request->email)->first();
        if ($user != null) {
            return response()->json([
                'message' => 'Reset Password token was successfully created.',
                // 'token' => app('auth.password.tokens')->create($user),
                'token' => Password::createToken($user),
            ]);
        } else {
            return response()->json([
                'message' => 'Email tidak ditemukan.',
            ]);
        }
    }

    public function updatePassword(Request $request)
    {
        $validator = validator()->make($request->only(['token', 'email', 'password', 'password_confirmation']), [
            'token' => ['required'],
            'email' => ['required', 'email', 'string', 'max:255'],
            'password' => ['required', 'confirmed', RulesPassword::min(7)->mixedCase()->numbers()->symbols()],
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
        $credentials = $validator->validated();
        if (($user = User::where('email', $credentials['email'])->first())) {
            if(Hash::check($credentials['password'],$user->password)){
                return response()->json(['message' => 'Password ini telah Anda pakai sebelumnya']);
            }
            foreach ($user->log_passwords()->get('password') as $key => $value) {
                if (Hash::check($credentials['password'], $value->password)) {
                    return response()->json(['message' => 'Password ini telah Anda pakai sebelumnya']);
                }
            };
        }
        $status = Password::reset(
            $credentials,
            function ($user) use ($request) {
                $user->forceFill([
                    'password' => Hash::make($request->password),
                    'remember_token' => \Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        return $status == Password::PASSWORD_RESET ? response()->json(['message' => 'Berhasil mengubah password']) : response()->json(['message' => __($status)]);
    }
}
