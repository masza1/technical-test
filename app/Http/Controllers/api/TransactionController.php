<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Carbon\Carbon;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    /**
     * @OA\Tag(
     *     name="Transaction",
     *     description="Transaction, Transfer, Withdraw, Topup"
     * ),
     * 
     * 
     * @OA\GET(
     *      tags={"Transaction"},
     *      path="/transactions/mutation",
     *      summary="Endpoint ini untuk laporan mutasi dalam periode waktu maks 30 hari",
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *          in="query",
     *          description="tanggal awal periode mutasi",
     *          name="start_date",
     *          @OA\Schema(type="date"),
     *          example="2022-02-05"
     *      ),
     *      @OA\Parameter(
     *          in="query",
     *          description="batas periode mutasi maks 30 hari",
     *          name="end_date",
     *          @OA\Schema(type="date"),
     *          example="2022-03-07"
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Akan memberikan data transaksi sesuai periode yang ditentukan"
     *      ),
     *      @OA\Response(
     *          response=422,
     *          description="Some variable is required"
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Unauthorized"
     *      ),
     * )
     * 
     **/
    public function mutation(Request $request)
    {
        $validator = validator()->make($request->all(), [
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $validated = $validator->validated();
        if (Carbon::parse($validated['start_date'])->addDays(30) <= $validated['end_date']) {
            return response()->json(['end_date' => 'Laporan mutasi maks 30 hari'], 422);
        }

        $mutations = auth()->user()->customer->transactions()->orderBy('updated_at', 'ASC')->get();
        if ($mutations->isEmpty()) {
            return response()->json(['message' => 'Tidak ada transaksi di periode ini.']);
        } else {
            return response()->json(['data' => $mutations, 'status' => 'success']);
        }
    }

    /**
     * 
     * @OA\POST(
     *      tags={"Transaction"},
     *      path="/transactions/topup",
     *      summary="Endpoint ini untuk melakukan transaksi pengisian saldo rekening",
     *      security={{"bearerAuth":{}}},
     *      @OA\RequestBody(
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(
     *                  @OA\Property(
     *                      property="description",
     *                      type="string",
     *                      description="deskripsi transaksi",
     *                      example="topup dari OVO"
     *                  ),
     *                  @OA\Property(
     *                      property="amount",
     *                      type="int",
     *                      description="jumlah topup minimal Rp20.000",
     *                      example="30000"
     *                  ),
     *                  example={
     *                      "description": "topup dari OVO",
     *                      "amount": "50000",
     *                  }
     *              )
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Akan memberikan pesan berhasil melakukan transaksi"
     *      ),
     *      @OA\Response(
     *          response=422,
     *          description="Some variable is required"
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Unauthorized"
     *      ),
     * )
     * 
     **/
    public function topup(Request $request)
    {
        return $this->transaction($request, 'TOPUP', 20000, 'Berhasil melakukan Top Up.', 'Gagal Melakukan Top Up');
    }

    /**
     * 
     * @OA\POST(
     *      tags={"Transaction"},
     *      path="/transactions/withdraw",
     *      summary="Endpoint ini untuk melakukan transaksi penarikan/withdraw",
     *      security={{"bearerAuth":{}}},
     *      @OA\RequestBody(
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(
     *                  @OA\Property(
     *                      property="description",
     *                      type="string",
     *                      description="deskripsi transaksi",
     *                      example="tarik tunai"
     *                  ),
     *                  @OA\Property(
     *                      property="amount",
     *                      type="int",
     *                      description="jumlah penarikan minimal dan kelipatan Rp50.000",
     *                      example="30000"
     *                  ),
     *                  example={
     *                      "description": "tarik tunai",
     *                      "amount": "100000",
     *                  }
     *              )
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Akan memberikan pesan berhasil melakukan transaksi"
     *      ),
     *      @OA\Response(
     *          response=422,
     *          description="Some variable is required"
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Unauthorized"
     *      ),
     * )
     * 
     **/
    public function withdraw(Request $request)
    {
        return $this->transaction($request, 'WITHDRAW', 50000, 'Berhasil melakukan Withdraw.', 'Gagal Melakukan Withdraw');
    }

    /**
     * 
     * @OA\POST(
     *      tags={"Transaction"},
     *      path="/transactions/transfer",
     *      summary="Endpoint ini untuk melakukan transaksi transfer ke rekening yang terdafar",
     *      security={{"bearerAuth":{}}},
     *      @OA\RequestBody(
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(
     *                  @OA\Property(
     *                      property="description",
     *                      type="string",
     *                      description="deskripsi transaksi",
     *                      example="beli sepatu shopee"
     *                  ),
     *                  @OA\Property(
     *                      property="amount",
     *                      type="int",
     *                      description="jumlah transfer minimal Rp20.000",
     *                      example="30000"
     *                  ),
     *                  @OA\Property(
     *                      property="rekening",
     *                      type="int",
     *                      description="rekening tujuan yang terdafar",
     *                      example="57998977"
     *                  ),
     *                  example={
     *                      "description": "beli sepatu shopee",
     *                      "amount": "100000",
     *                      "rekening": 57998977,
     *                  }
     *              )
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Akan memberikan pesan berhasil melakukan transaksi"
     *      ),
     *      @OA\Response(
     *          response=422,
     *          description="Some variable is required"
     *      ),
     *      @OA\Response(
     *          response=403,
     *          description="Unauthorized"
     *      ),
     * )
     * 
     **/
    public function transfer(Request $request)
    {
        return $this->transaction($request, 'TRANSFER', 20000, 'Transfer berhasil.', 'Gagal melakukan transfer.', 'required');
    }

    private function transaction(Request $request, $type, $min, $message, $errorMessage, $transfer = 'nullable')
    {
        $validator = validator()->make($request->all(), [
            'description' => ['nullable', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:' . $min, 'digits_between:5,20'],
            'rekening' => [$transfer, 'numeric', 'digits_between:1,50'],
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $validated = $validator->validated();
        $validated['type'] = $type;
        if ($type == 'WITHDRAW') {
            if ($validated['amount'] % 50000 != 0) {
                return response()->json(['message' => 'Penarikan harus kelipatan Rp50.000.']);
            }
        }

        if ($validated['description'] == null || trim($validated['description']) == '') {
            $validated['description'] = $type;
        }

        $customer = auth()->user()->customer;
        $validated['old_saldo'] = $customer->saldo;

        $destination = null;
        if ($type == 'TRANSFER') {
            $destination = Customer::where('rekening', $validated['rekening'])->where('rekening', '!=', $customer->rekening)->first();
            if ($destination == null) {
                return response()->json(['message' => 'Rekening tujuan tidak ditemukan.'], 201);
            }
        }

        if (($transaction = $customer->transactions()->create($validated))) {
            if ($type == 'TOPUP') {
                $validated['amount'] = $customer->saldo + $validated['amount'];
            } else if ($type == 'WITHDRAW' || $type == 'TRANSFER') {
                $validated['amount'] = $customer->saldo - $validated['amount'];
            }

            if ($customer->update(['saldo' => $validated['amount']])) {
                if ($type == 'TRANSFER') {
                    if (($$transaction = $destination->transactions()->create([
                        'type' => 'TOPUP',
                        'description' => 'transfer dari '. $customer->rekening,
                        'old_saldo' => $destination->saldo,
                        'amount' => $request->amount
                    ]))) {
                        if($destination->update(['saldo' => $destination->saldo + $request->amount])){
                            return response()->json(['message' => $message]);
                        }
                        $$transaction->delete();
                    }
                }
                return response()->json(['message' => $message]);
            }
            $transaction->delete();
        }
        return response()->json(['message' => $errorMessage]);
    }
}
