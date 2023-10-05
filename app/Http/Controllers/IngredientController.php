<?php

namespace App\Http\Controllers;

use App\Ingredient;
use App\Ingredient_stock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class IngredientController extends Controller
{
    public function insertingredient(Request $request)
    {
        $validator = Validator::make($request->all(),
            [
                'merchant_id' => 'required',
                'name' => 'required|unique:ingredients|max:255',
                'unit' => 'required|max:255',
                'amount' => 'required|numeric|between:0.01,99999999.99',
                'minimum_amount' => 'required|numeric|between:0.01,99999999.99',
                'expired_at' => 'required|date'
            ]);
        $messages = $validator->errors();
        if ($validator->fails())
        {
            $out = [
                "message" => $messages->first(),
                "code"   => 200
            ];
            return response()->json($out, $out['code']);
        };

        DB::beginTransaction();
        try {
            //initialization
            $merchant_id = $request->input('merchant_id');
            $name = $request->input('name');
            $unit = $request->input('unit');
            $amount = $request->input('amount');
            $minimum_amount = $request->input('minimum_amount');
            $expired_at = $request->input('expired_at');
            //creating ingredient
            $data = [
                'merchant_id' => $merchant_id,
                'name' => $name,
                'unit' => $unit,
                'expired_at' => $expired_at,
            ];
            Ingredient::create($data);
            //get the ingredient id
            $ingredient_id = Ingredient::max('id');
            //creating ingredient stock
            $datastock = [
                'merchant_id' => 1,
                'ingredient_id' => $ingredient_id,
                'amount' => $amount,
                'minimum_amount' => $minimum_amount
            ];
            Ingredient_stock::create($datastock);
            DB::commit();
            $out = [
                'message' => "InsertIngredient - Success",
                'code' => 200
            ];
            return response()->json($out,$out['code']);
        }catch (\exception $e) { //database tidak bisa diakses
            DB::rollback();
            $message = $e->getmessage();
            $out  = [
                "message" => $message
            ];
            return response()->json($out,200);
        };
    }

    public function index()
    {
        $getPost = Ingredient::leftjoin('ingredient_stock', 'ingredient_stock.ingredient_id', '=', 'ingredients.id')
        ->select('ingredients.id','name','amount', 'unit')
        ->OrderBy("ingredients.id", "DESC")
        ->get();

        $out = [
            "message" => "List Ingredient",
            "results" => $getPost,
            "code" => 200
        ];

        return response()->json($out, $out['code']);
    }

    public function updateingredient($id, Request $request)
    {
        if ($request->isMethod('post'))
        {
            $validator = Validator::make($request->all(),
            [
                'merchant_id' => 'required',
                'name' => 'required|max:255',
                'unit' => 'required|max:255',
                'amount' => 'required|numeric|between:0.01,99999999.99',
                'minimum_amount' => 'required|numeric|between:0.01,99999999.99',
                'expired_at' => 'required|date',
            ]);
            $messages = $validator->errors();
            if ($validator->fails()) {
                $out = [
                    "message" => $messages->first(),
                    "code"   => 200
                ];
            return response()->json($out, $out['code']);
            };

            DB::beginTransaction();
            try {
                //initialize
                $merchant_id = $request->input('merchant_id');
                $name = $request->input('name');
                $unit = $request->input('unit');
                $amount = $request->input('amount');
                $minimum_amount = $request->input('minimum_amount');
                $expired_at = $request->input('expired_at');
                //updating ingredient
                $oldingredient = Ingredient::where("id","=",$id);
                $data = [
                    "merchant_id" => $merchant_id,
                    "name" => $name,
                    "unit" => $unit,
                    'expired_at' => $expired_at,
                ];
                $oldingredient -> update($data);
                //updating ingredient stock
                $oldingredientstock = Ingredient_stock::where("ingredient_id","=",$id);
                $datastock = [
                    "merchant_id" => $merchant_id,
                    "ingredient_id" => $id,
                    "amount" => $amount,
                    "minimum_amount" => $minimum_amount
                ];
                    $oldingredientstock -> update($datastock);
                DB::commit();
                $out  = [
                    "message" => "EditIngredient - Success",
                    "code" => 200
                ];
                return response()->json($out,$out['code']);
            }catch (\exception $e) { //database tidak bisa diakses
                DB::rollback();
                $message = $e->getmessage();
                $out  = [
                    "message" => $message
                ];
                return response()->json($out,200);
            };
        };
    }

    public function destroy($id)
    {
        $ingredient =  Ingredient::where('id','=',$id)->first();
        $oldingredientstock = Ingredient_stock::where("ingredient_id","=",$id);

        if (!$ingredient) {
            $data = [
                "message" => "error / data not found",
                "code" => 200
            ];
        } else {
            $ingredient->delete();
            $oldingredientstock -> delete();
            $data = [
                "message" => "DeleteIngredient - Success",
                "code" => 200
            ];
        };
        return response()->json($data, $data['code']);
    }
}
