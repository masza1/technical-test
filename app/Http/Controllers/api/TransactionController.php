<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Carbon\Carbon;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
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

    public function topup(Request $request)
    {
        return $this->transaction($request, 'TOPUP', 20000, 'Berhasil melakukan Top Up.', 'Gagal Melakukan Top Up');
    }

    public function withdraw(Request $request)
    {
        return $this->transaction($request, 'WITHDRAW', 50000, 'Berhasil melakukan Withdraw.', 'Gagal Melakukan Withdraw');
    }

    public function transfer(Request $request)
    {
        return $this->transaction($request, 'TRANSFER', 20000, 'Transfer berhasil.', 'Gagal melakukan transfer.');
    }

    private function transaction(Request $request, $type, $min, $message, $errorMessage)
    {
        $validator = validator()->make($request->all(), [
            'type' => ['required', 'in:' . $type],
            'description' => ['nullable', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:' . $min, 'digits_between:5,20'],
            'rekening' => ['nullable', 'numeric', 'digits_between:1,50'],
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $validated = $validator->validated();
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
                return response()->json(['message' => 'Rekening tujuan tidak ditemukan.'], 200);
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
