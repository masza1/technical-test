<?php

use App\Http\Controllers\api\{
    AuthController,
    IndexController,
    ResetPasswordController,
    TransactionController
};
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
Route::post('/logic-test-containers', [IndexController::class, 'logicTest']);

Route::group([
    'middleware' => 'api',
    'prefix' => 'auth'
], function ($router) {
    Route::get('login', function(){
        if(auth()->check()){
            return redirect()->to('api/auth/me');
        }else{
            return response()->json(['message' => 'Anda belum login!'], 403);
        }
    })->name('login');

    Route::post('forgot-password', [ResetPasswordController::class, 'createLinkResetPassword']);
    Route::put('update-password', [ResetPasswordController::class, 'updatePassword']);

    Route::post('login', [AuthController::class, 'login']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('register', [AuthController::class, 'register'])->middleware('guest');
    Route::post('refresh', [AuthController::class, 'refresh']);
    Route::get('me', [AuthController::class, 'me']);
});

Route::prefix('transactions')->middleware(['auth'])->group(function(){
    Route::get('mutation', [TransactionController::class, 'mutation']);
    Route::post('topup', [TransactionController::class, 'topup']);
    Route::post('transfer', [TransactionController::class, 'transfer']);
    Route::post('withdraw', [TransactionController::class, 'withdraw']);
});
