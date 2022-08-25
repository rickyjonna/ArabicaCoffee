<?php

namespace App\Http\Controllers;

use App\Product_category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; //pake facades DB
use Validator, Input, Redirect;

class ProductCategoryController extends Controller
{
    public function insertproductcategory(Request $request)
    {
        if ($request->isMethod('post')) 
        {
            $validator = Validator::make($request->all(), [
                'information' => 'required||unique:product_category'         
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
                //initialize
                $information = $request->input('information'); 

                //making Table
                $data = [
                    'information' => $information
                ];
                $insert = Product_category::create($data);
                DB::commit();
                $out  = [
                    "message" => "InsertProductCategory - Success",
                    "code"  => 200
                ];
                return response()->json($out, $out['code']);
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

    public function index()
    {
        $getPost = Product_category::OrderBy("id", "ASC")
        ->pluck('information');

        $out = [
            "message" => "List Kategori Produk",
            "results" => $getPost,
            "code" => 200
        ];
        return response()->json($out, $out['code']);
    }

    public function updateproductcategory($id, Request $request)
    {
        if ($request->isMethod('post')) 
        {
            $validator = Validator::make($request->all(), [
                'information' => 'required'
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
                //initialize
                $information = $request->input('information');

                //updating old product_category
                $oldproductcategory = Product_category::where('id','=',$id);
                $data = [
                    'information' => $information
                ];
                $oldproductcategory -> update($data);
                DB::commit();
                $out  = [
                    "message" => "EditProductCategory - Success",
                    "code"  => 200
                ];
                return response()->json($out, $out['code']);     
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
        $product_category =  Product_category::where('id','=',$id)->first();

        if (!$product_category) {
            $data = [
                "message" => "error / data not found",
                "code" => 200
            ];
        } else {
            $product_category->delete();
            $data = [
                "message" => "DeleteProductCategory - Success",
                "code" => 200
            ];
        };
        return response()->json($data, $data['code']);
    }
}