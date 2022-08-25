<?php

namespace App\Http\Controllers;

use App\Product_formula;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; //pake facades DB
use Validator, Input, Redirect;

class ProductFormulaController extends Controller
{
    public function __construct()
    {
        $this->middleware("login");
    }

    public function index()
    {
        $getPost = Product_formula::leftjoin('products', 'products.id', '=', 'product_formula.product_id')
        ->leftjoin('ingredients','ingredients.id', '=', 'product_formula.ingredient_id')
        ->addselect('products.name as Produk', 'ingredients.name as Bahan','amount as Jumlah') 
        ->OrderBy("product_formula.id", "ASC")
        ->get();

        $out = [
            "message" => "List formula",
            "results" => $getPost
        ];
        return response()->json($out, 200);
    }

    public function updateformula($id, Request $request)
    {
        if ($request->isMethod('patch')) 
        {
            $validator = Validator::make($request->all(), 
            [
                'amount' => 'required|integer'
            ]);
            $messages = $validator->errors();
            if ($validator->fails()) {
                $out = [
                    "message" => $messages->first(),
                    "code"   => 400
                ];
            return response()->json($out, $out['code']);
            };

            DB::beginTransaction();
            try {
                //initialize
                $amount = $request->input('amount'); 

                //updating old formula
                $oldformula = Product_formula::where("id","=",$id);
                $data = [
                    "amount" => $amount
                ];
                $insertformula = $oldformula -> update($data);
                DB::commit();
                $out  = [
                    "message" => "Formula Telah Diperbaharui",
                    "code"  => 200
                ];
                return response()->json($out, $out['code']);        
            }catch(\eception $e){
                DB::rollback();
                $errorcode = $e->getcode();
                $message = $e->getmessage();
                if ($e instanceof \PDOException){ //dikarenakan kalau ada inputan empty mengakibatkan $e tidak catch apa2(kosong) maka dibuat code ini << belum
                    $out = [
                        "message" => $message,
                        "code" => 400
                    ];
                } else {
                    $out = [
                        "message" => "Error, Ada Inputan Kosong",
                        "code" => 400
                    ];
                };
                return response()->json($out,$out['code']);  
            };
        };
    }

    public function destroy($id)
    {
        $formula =  Product_formula::where('id','=',$id)->first();

        if (!$formula) {
            $data = [
                "message" => "error / data not found",
                "code" => 404
            ];
        } else {
            $formula->delete();
            $data = [
                "message" => "success deleted",
                "code" => 200
            ];
        };
        return response()->json($data, $data['code']);
    }
}
