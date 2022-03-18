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
    /**
     * 
     * @OA\POST(
     *      tags={"Authentication"},
     *      path="/auth/forgot-password",
     *      summary="Endpoint ini untuk mendapatkan token lupa password",
     *      security={{"bearerAuth":{}}},
     *      @OA\RequestBody(
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(
     *                  @OA\Property(
     *                      property="email",
     *                      type="email",
     *                      description="email yang terdaftar",
     *                      example="ybrakus@example.net"
     *                  ),
     *                  example={
     *                      "email": "ybrakus@example.net",
     *                  }
     *              )
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Akan memberikan token lupa password"
     *      ),
     *      @OA\Response(
     *          response=201,
     *          description="Email tidak ditemukan"
     *      ),
     *      @OA\Response(
     *          response=422,
     *          description="Some variable is required"
     *      ),
     * )
     * 
     **/
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
            ],201);
        }
    }

    /**
     * 
     * @OA\POST(
     *      tags={"Authentication"},
     *      path="/auth/update-password",
     *      summary="Endpoint ini untuk mengubah password",
     *      security={{"bearerAuth":{}}},
     *      @OA\RequestBody(
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(
     *                  @OA\Property(
     *                      property="_method",
     *                      type="string",
     *                      description="property untuk mengizinkan menggunakan method PUT",
     *                      example="put"
     *                  ),
     *                  @OA\Property(
     *                      property="email",
     *                      type="email",
     *                      description="email yang terdaftar",
     *                      example="ybrakus@example.net"
     *                  ),
     *                  @OA\Property(
     *                      property="token",
     *                      type="string",
     *                      description="token yang didapat ketika melakukan lupa password",
     *                      example="kjabkdaisdgihiaushd"
     *                  ),
     *                  @OA\Property(
     *                      property="password",
     *                      type="password",
     *                      description="Password baru, terdiri dari minimal 7 karakter, huruf besar dan kecil, angka dan simbol, dan bukan password yang sudah digunakan sebelumnya",
     *                      example="Password3!"
     *                  ),
     *                  @OA\Property(
     *                      property="password_confirmation",
     *                      type="password",
     *                      description="ulangi password",
     *                      example="Password3!"
     *                  ),
     *                  example={
     *                      "_method": "PUT",
     *                      "email": "ybrakus@example.net",
     *                      "token": "asdasdasdadasda",
     *                      "password": "Password3!",
     *                      "password_confirmation": "Password3!",
     *                  }
     *              )
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Akan memberikan pesan berhasil mengubah password"
     *      ),
     *      @OA\Response(
     *          response=201,
     *          description="Password gagal diubah/ password sudah pernah digunakan"
     *      ),
     *      @OA\Response(
     *          response=422,
     *          description="Some variable is required"
     *      ),
     * )
     * 
     **/
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
                return response()->json(['message' => 'Password ini telah Anda pakai sebelumnya'], 201);
            }
            foreach ($user->log_passwords()->get('password') as $key => $value) {
                if (Hash::check($credentials['password'], $value->password)) {
                    return response()->json(['message' => 'Password ini telah Anda pakai sebelumnya'], 201);
                }
            };
        }
        $status = Password::reset(
            $credentials,
            function ($user) use ($request) {
                $user->log_passwords()->create(['password' => $user->password]);
                $user->forceFill([
                    'password' => Hash::make($request->password),
                    'remember_token' => \Str::random(60),
                ])->save();

                event(new PasswordReset($user));

            }
        );

        return $status == Password::PASSWORD_RESET ? response()->json(['message' => 'Berhasil mengubah password'], 200) : response()->json(['message' => __($status)], 201);
    }
}
