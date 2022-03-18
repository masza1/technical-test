<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class IndexController extends Controller
{
    /**
     *  @OA\Tag(
     *     name="Logic Test Container",
     *     description="Logic Test Container"
     * )
     * 
     * @OA\POST(
     *      tags={"Logic Test Container"},
     *      path="/logic-test-containers",
     *      summary="Endpoint ini untuk soal logika test penempatan kontainer",
     *      @OA\RequestBody(
     *          @OA\MediaType(
     *              mediaType="application/json",
     *              @OA\Schema(
     *                  @OA\Property(
     *                      property="containers[]",
     *                      type="array",
     *                      @OA\Items(
     *                          type="int"
     *                      )
     *                  ),
     *                  example={"containers": {1234567,"0123456",1234547}}
     *              )
     *          ),
     *          @OA\MediaType(
     *              mediaType="multipart/data-form",
     *              @OA\Schema(
     *                  @OA\Property(
     *                      property="containers[]",
     *                      type="int",
     *                      example=1234547
     *                  ),
     *              )
     *          ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Akan mengembalikan nilai antara LEFT, RIGHT, CENTER, DEAD"
     *      ),
     *      @OA\Response(
     *          response=422,
     *          description="containers[] value tidak sesuai dengan ketentuan"
     *      )
     * )
     * 
     **/
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
