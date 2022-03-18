<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
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
     *  @OA\Tag(
     *     name="Authentication",
     *     description="authentication"
     * )
     * 
     * @OA\POST(
     *      tags={"Authentication"},
     *      path="/auth/login",
     *      summary="Endpoint ini untuk authentication login",
     *      @OA\RequestBody(
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(
     *                  @OA\Property(
     *                      property="email",
     *                      type="email",
     *                      example="maszahid1@gmail.com"
     *                  ),
     *                  @OA\Property(
     *                      property="password",
     *                      type="string",
     *                      description="Minimal 7 karakte, harus terdapat huruf kecil dan besar, angka dan simbol"
     *                  ),
     *                  example={
     *                      "email": "ybrakus@example.net",
     *                      "password": "Password2!"
     *                  }
     *              )
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Akan memberikan token authentication, tipe token dan batas expired"
     *      ),
     *      @OA\Response(
     *          response=422,
     *          description="Some variable is required"
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Unathorized"
     *      ),
     * )
     * 
     **/

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
     * @OA\SecurityScheme(
     *    securityScheme="bearerAuth",
     *    in="header",
     *    name="bearerAuth",
     *    type="http",
     *    scheme="bearer",
     *    bearerFormat="JWT",
     * ),
     * 
     * @OA\GET(
     *      tags={"Authentication"},
     *      path="/auth/me",
     *      summary="Endpoint ini untuk mendapatkan data user yang sedang login",
     *      security={{"bearerAuth":{}}},
     *      @OA\Response(
     *          response=200,
     *          description="Akan memberikan data user yang sedang login"
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Belum melakukan login"
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Unathorized"
     *      ),
     * )
     * 
     **/
    public function me()
    {
        return response()->json(auth()->user()->with('customer')->first());
    }

    /**
     * 
     * @OA\POST(
     *      tags={"Authentication"},
     *      path="/auth/logout",
     *      summary="Endpoint ini untuk logout user yang sedang login",
     *      security={{"bearerAuth":{}}},
     *      @OA\Response(
     *          response=200,
     *          description="Akan memberikan pesan berhasil logout"
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Belum melakukan login"
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Unathorized"
     *      ),
     * )
     * 
     **/
    public function logout()
    {
        auth()->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    /**
     * 
     * @OA\POST(
     *      tags={"Authentication"},
     *      path="/auth/refresh",
     *      summary="Endpoint ini untuk mendapatkan token baru user yang sedang login",
     *      security={{"bearerAuth":{}}},
     *      @OA\Response(
     *          response=200,
     *          description="Akan memberikan token baru"
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Belum melakukan login"
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Unathorized"
     *      ),
     * )
     * 
     **/
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


    /**
     * 
     * @OA\POST(
     *      tags={"Authentication"},
     *      path="/auth/register",
     *      summary="Endpoint ini untuk registrasi akun baru",
     *      security={{"bearerAuth":{}}},
     *      @OA\RequestBody(
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(
     *                  @OA\Property(
     *                      property="email",
     *                      type="email",
     *                      description="value harus berupa email address",
     *                      example="maszahid1@gmail.com"
     *                  ),
     *                  @OA\Property(
     *                      property="password",
     *                      type="string",
     *                      description="Minimal 7 karakte, harus terdapat huruf kecil dan besar, angka dan simbol",
     *                      example="Abcdefg1!"
     *                  ),
     *                  @OA\Property(
     *                      property="password_confirmation",
     *                      type="string",
     *                      description="password konfirmasi harus sama",
     *                      example="Abcdefg1!"
     *                  ),
     *                  @OA\Property(
     *                      property="firstname",
     *                      type="string",
     *                      description="nama depan",
     *                      example="Alexander"
     *                  ),
     *                  @OA\Property(
     *                      property="lastname",
     *                      type="string",
     *                      description="nama belakang",
     *                      example="Graham Bell"
     *                  ),
     *                  @OA\Property(
     *                      property="bank_name",
     *                      type="string",
     *                      description="Nama Bank yang akan didaftarkan",
     *                      example="BCA/BNI/BRI"
     *                  ),
     *                  @OA\Property(
     *                      property="rekening",
     *                      type="int",
     *                      description="Nomor Rekening",
     *                      example="57998977"
     *                  ),
     *                  @OA\Property(
     *                      property="saldo",
     *                      type="int",
     *                      description="Setoran Pertama, minimal Rp50.000",
     *                      example="50000"
     *                  ),
     *                  example={
     *                      "email": "testing@example.net",
     *                      "password": "Password2!",
     *                      "password_confirmation": "Password2!",
     *                      "firstname": "Alexander",
     *                      "lastname": "Graham Bell",
     *                      "bank_name": "BCA",
     *                      "rekening": 57998977,
     *                      "saldo": 50000,
     *                  }
     *              )
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Akan memberikan data user yang sudah terdaftar dan pesan berhasil mendaftar"
     *      ),
     *      @OA\Response(
     *          response=422,
     *          description="Some variable is required"
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="duplicate entry"
     *      ),
     * )
     * 
     **/
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
        try {
            event(new Registered($user));
            auth()->login($user);

            if (auth()->user()->customer()->create($validated)) {
                return response()->json(['data' => $user,'message' => 'Berhasil membuat akun Nasabah.'], 200);
            }
        } catch (QueryException $err) {
            if ($err->getCode() == 23000) {
                if ($err->errorInfo[1] == 1062) {
                    return response()->json(['message' => 'Duplicate Entry'], 403);
                }
            }
        }
    }
}
