<?php

namespace App\Http\Controllers;

use App\Product_stock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; //pake facades DB
use Validator, Input, Redirect;

class ProductStockController extends Controller
{
    public function index()
    {
        $stock = Product_stock::leftjoin('products', 'products.id', '=', 'product_stock.product_id')
        ->addselect('products.name as Produk', 'amount as Jumlah') 
        ->OrderBy("products.id", "ASC")
        ->get();

        $result = [
            "stock" => $stock
        ];

        $out = [
            "message" => "List Stock",
            "results" => $result
        ];
        return response()->json($out, 200);
    }

    public function updatestock($id, Request $request)
    {
        if ($request->isMethod('patch')) 
        {
            $validator = Validator::make($request->all(), 
            [
                'merchant_id' => 'required|integer',
                'amount' => 'required|integer',
                'minimum_amount' => 'required|integer'
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
                $merchant_id = $request->input('merchant_id');
                $amount = $request->input('amount');
                $minimum_amount = $request->input('minimum_amount');
                //updating old stock
                $oldstock = Product_stock::where("id","=",$id);
                $data = [
                    "merchant_id" => $merchant_id,
                    "amount" => $amount,
                    "minimum_amount" => $minimum_amount
                ];
                $updatestock = $oldstock -> update($data);
                DB::commit();
                $out  = [
                    "message" => "Stock Telah Diperbaharui",
                    "code" => 200
                ];               
                return response()->json($out,$out['code']);
            }catch(\exception $e) {
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
        $stock =  Product_stock::where('id','=',$id)->first();

        if (!$stock) {
            $data = [
                "message" => "error / data not found",
            ];
        } else {
            $stock->delete();
            $data = [
                "message" => "success deleted"
            ];
        };
        return response()->json($data, 200);
    }
}
