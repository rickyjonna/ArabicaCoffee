<?php

namespace App\Http\Controllers;

use App\Product_stock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; //pake facades DB
use Illuminate\Support\Facades\Validator;

class ProductStockController extends Controller
{
    public function index()
    {
        $stock = Product_stock::leftjoin('products', 'products.id', '=', 'product_stock.product_id')
        ->addselect('product_stock.id', 'products.name as Produk', 'amount as Jumlah', 'minimum_amount', 'unit')
        ->OrderBy("products.id", "ASC")
        ->get();

        $result = [
            "productstock" => $stock
        ];

        $out = [
            "message" => "List Stock",
            "results" => $result
        ];
        return response()->json($out, 200);
    }

    public function updatestock($id, Request $request)
    {
        if ($request->isMethod('post'))
        {
            $validator = Validator::make($request->all(),
            [
                'amount' => 'required|integer',
                'minimum_amount' => 'required|integer'
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
                $amount = $request->input('amount');
                $minimum_amount = $request->input('minimum_amount');
                //updating old stock
                $oldstock = Product_stock::where("id","=",$id);
                $data = [
                    "amount" => $amount,
                    "minimum_amount" => $minimum_amount
                ];
                $oldstock -> update($data);
                DB::commit();
                $out  = [
                    "message" => "EditStock - Success",
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
