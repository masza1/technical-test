<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'register']]);
    }

    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login()
    {
        $validator = validator()->make(request()->only(['email', 'password']), [
            'email' => ['required', 'email', 'string', 'max:255'],
            'password' => ['required', Password::min(7)->mixedCase()->numbers()->symbols()],
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
        $credentials = $validator->validated();

        if (!$token = auth()->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        auth()->user()->log_activities()->create();
        return $this->respondWithToken($token);
    }

    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me()
    {
        return response()->json(auth()->user()->with('customer')->first());
    }

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        auth()->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        return $this->respondWithToken(auth()->refresh());
    }

    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60
        ]);
    }

    public function register(Request $request)
    {
        $validator = validator()->make($request->all(), [
            'email' => ['required', 'email', 'string', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::min(7)->mixedCase()->numbers()->symbols()],
            'firstname' => ['required', 'string', 'max:50'],
            'lastname' => ['required', 'string', 'max:50'],
            'bank_name' => ['required', 'string', 'max:255'],
            'saldo' => ['required', 'numeric', 'min:50000', 'digits_between:5,20'],
            'rekening' => ['required', 'numeric', 'digits_between:8,15'],
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $validated = $validator->validate();
        $validated['password'] = Hash::make($validated['password']);
        $user = User::create($validated);
        unset($validated['email']);
        unset($validated['password']);
        try{
            event(new Registered($user));
            auth()->login($user);

            if(auth()->user()->customer()->create($validated)){
                return response()->json(['message' => 'Berhasil membuat akun Nasabah.'], 200);
            }
        }catch(QueryException $err){
            if($err->getCode() == 23000){
                if($err->errorInfo[1] == 1062){
                    return response()->json(['message' => 'Duplicate Entry'], 403);
                }
            }
        }
    }
}
