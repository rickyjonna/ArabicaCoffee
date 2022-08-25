<?php

namespace App\Http\Controllers;

use App\Ingredient;
use App\Ingredient_stock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Validator, Input, Redirect;

class IngredientController extends Controller
{
    public function insertingredient(Request $request)
    {
        $validator = Validator::make($request->all(), 
            [
                'name' => 'required|unique:ingredients|max:255',
                'unit' => 'required|max:255',
                'amount' => 'required|integer',
                'minimum_amount' =>'required|integer'
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
            $name = $request->input('name');
            $unit = $request->input('unit');
            $amount = $request->input('amount');
            $minimum_amount = $request->input('minimum_amount');
            //creating ingredient
            $data = [
                'merchant_id' => 1,
                'name' => $name,
                'unit' => $unit
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
                'name' => 'required|max:255',
                'unit' => 'required|max:255',
                'amount' => 'required|integer'
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
                $name = $request->input('name');
                $unit = $request->input('unit');
                $amount = $request->input('amount');
                $minimum_amount = $request->input('minimum_amount');
                //updating ingredient
                $oldingredient = Ingredient::where("id","=",$id);
                $data = [
                    "merchant_id" => 1,
                    "name" => $name,
                    "unit" => $unit
                ];
                $updateingredient = $oldingredient -> update($data);
                //updating ingredient stock
                $oldingredientstock = Ingredient_stock::where("ingredient_id","=",$id);
                $datastock = [
                    "merchant_id" => 1,
                    "ingredient_id" => $id,
                    "amount" => $amount,
                    "minimum_amount" => $minimum_amount
                ];
                $updateingredientstock = $oldingredientstock -> update($datastock);
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
