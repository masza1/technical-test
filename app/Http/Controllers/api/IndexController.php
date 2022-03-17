<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class IndexController extends Controller
{
    public function logicTest(Request $request)
    {
        $validator = validator()->make($request->all(), [
            'containers' => ['required', 'array'],
            'containers.*' => ['numeric', 'digits_between:7,7'],
        ]);

        if($validator->fails()){
            return response()->json($validator->errors(), 422);
        }
        $validated = $validator->validated();

        $result = [];
        foreach ($validated['containers'] as $key => $value) {
            if(str_contains($value, 0)){
                array_push($result,"DEAD");
            }else{
                if($this->checkPrimes($value)){
                    $value = substr($value, 3);
                    if($this->checkPrimes($value)){
                        array_push($result,"CENTRAL");
                    }else if(count(array_count_values(str_split(substr($value, 1)))) == 1){
                        array_push($result,"RIGHT");
                    }else if(substr($value, -2,1) < substr($value, -1) && $this->checkPrimes(substr($value, 2))){
                        array_push($result,"LEFT");
                    }else{
                        array_push($result,"DEAD");
                    }
                }else{
                    array_push($result,"DEAD");
                }
            }
        }
        return response()->json($result, 200);
    }

    private function checkPrimes($container): bool
    {
        $result = true;
        for ($i=2; $i <$container; $i++) { 
            if($container % $i == 0){
                $result = false;
            }
        }
        return $result;
    }
}
