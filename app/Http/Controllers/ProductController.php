<?php

namespace App\Http\Controllers;

use App\Ingredient;
use App\Ingredient_stock;
use App\Product;
use App\Product_category;
use App\Product_formula;
use App\Product_price_agent;
use App\Product_stock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; //pake facades DB
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    // public function buatproduk()
    public function insertproduct(Request $request)
    {
        if ($request->isMethod('post'))
        {
            $validator = Validator::make($request->all(),
            [
                'merchant_id' => 'required|integer',
                'partner_id' => 'required|integer',
                'product_category_id' => 'required|integer',
                'name' => 'required|unique:products|max:255',
                'price' => 'required|max:10',
                'discount' => 'required|max:4',
                'isformula' => 'required',
                'hasstock' => 'required',
                'information' => 'nullable|max:255',
                'amount' => 'nullable',
                'minimum_amount' => 'nullable',
                'unit' => 'nullable',
                'ingredient' => 'nullable|array',
                'agent_price' => 'nullable|array'
            ]);
            $messages = $validator->errors();
            if ($validator->fails()) {
                $out = [
                    "message" => $messages->first()
                ];
            return response()->json($out, 200);
            };

            DB::beginTransaction();
            try{
                $partner_id = $request->input('partner_id');
                $merchant_id = $request->input('merchant_id');
                $product_category_id = $request->input('product_category_id');
                $name = $request->input('name');
                $price = $request->input('price');
                $discount = $request->input('discount');
                $isformula = $request->input('isformula');
                $hasstock = $request->input('hasstock');
                $information = $request->input('information');
                $agent_price = $request->input('agent_price');

                //making product
                $dataproduct = [
                    'merchant_id' => $merchant_id,
                    'partner_id' => $partner_id,
                    'product_category_id' => $product_category_id,
                    'name' => $name,
                    'price' => $price,
                    'discount' => $discount,
                    'hasstock' => $hasstock,
                    'isformula' => $isformula,
                    'information' => $information,
                    'editable' => 1
                ];
                Product::create($dataproduct);
                $newproductid = Product::max("id");

                //making stock
                if ($hasstock == 1)
                {
                    $amount = $request->input('amount');
                    $minimum_amount = $request->input('minimum_amount');
                    $unit = $request->input('unit');
                    $datastock = [
                        'merchant_id' => $merchant_id,
                        'product_id' => $newproductid,
                        'amount' => $amount,
                        'minimum_amount' => $minimum_amount,
                        'unit' => $unit
                    ];
                    Product_stock::create($datastock);
                };

                //making formula
                if ($isformula == 1)
                {
                    $ingredient = $request->input('ingredient');
                    $total_ingredientarray = count($ingredient) / 2;
                    for ($i=0; $i<$total_ingredientarray; $i++)
                    {
                        $dataformula =[
                            'merchant_id' => $merchant_id,
                            'product_id' => $newproductid,
                            'ingredient_id' => $ingredient[0],
                            'amount' => $ingredient[1]
                            ];
                        Product_formula::create($dataformula);
                        $ingredient = array_splice($ingredient,2);
                    };
                };

                //making agent_price
                if ($agent_price)
                {
                    $total_agentpricearray = count($agent_price) / 2;
                    for ($i=0; $i<$total_agentpricearray; $i++) {
                        $dataagentprice = [
                            'merchant_id' => $merchant_id,
                            'product_id' => $newproductid,
                            'agent_id' => $agent_price[0],
                            'agent_price' => $agent_price[1]
                        ];
                        Product_price_agent::create($dataagentprice);
                        $agent_price = array_splice($agent_price, 2);
                    };
                };

                DB::commit();
                $out  = [
                    "message" => "InsertProduct - Success"
                ];
                return response()->json($out,200);

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

    public function updateproduct($id, Request $request)
    {
        if ($request->isMethod('post'))
        {
            //validasi
            $validator = Validator::make($request->all(),
            [
                'merchant_id' => 'required|integer',
                'partner_id' => 'required|integer',
                'product_category_id' => 'required|integer',
                'name' => 'required|max:255',
                'price' => 'required|max:10',
                'discount' => 'required|max:4',
                'isformula' => 'required',
                'hasstock' => 'required',
                'information' => 'nullable|max:255',
                'amount' => 'nullable|integer',
                'minimum_amount' => 'nullable|integer',
                'ingredient' => 'nullable|array',
                'agent_price' => 'nullable|array'
            ]);
            $messages = $validator->errors();
            if ($validator->fails()) {
                $out = [
                    "message" => $messages->first()
                ];
            return response()->json($out, 200);
            };

            DB::beginTransaction();
            try{
                $merchant_id = $request->input('merchant_id');
                $partner_id = $request->input('partner_id');
                $product_category_id = $request->input('product_category_id');
                $name = $request->input('name');
                $price = $request->input('price');
                $discount = $request->input('discount');
                $isformula = $request->input('isformula');
                $hasstock = $request->input('hasstock');
                $information = $request->input('information');
                $newproductdata = [
                    'merchant_id' => $merchant_id,
                    'partner_id' => $partner_id,
                    'product_category_id' => $product_category_id,
                    'name' => $name,
                    'price' => $price,
                    'discount' => $discount,
                    'hasstock' => $hasstock,
                    'isformula' => $isformula,
                    'information' => $information,
                    'editable' => 1
                ];

                //getting old product record
                $oldproduct = Product::where('id','=',$id);
                $oldproductprice = Product::where('id','=',$id)->max('price');

                //checking the price
                $price = $request->input('price');
                if($price == $oldproductprice)
                {
                    //1.update old product
                    $oldproduct -> update($newproductdata);

                    //2.updating product_stock
                    if ($hasstock == 1)
                    {
                        // update_stock($request,$id,$id);
                    };

                    //3.updating formula
                    $isformula = $request->input('isformula');
                    if ($isformula == 1)
                    {
                        // update_formula($request,$id,$id);
                    };

                    //4.updating agent_price
                    // $agent_price = $request -> input('agent_price');
                    // if ($agent_price){
                    //     $agent_pricecount = count($agent_price) / 2;
                    //     for ($i=0;$i<$agent_pricecount; $i++){
                    //         $agent_id = $agent_price[0];
                    //         $agent_pricex = $agent_price[1];
                    //         $old_row = Product_price_agent::where('product_id','=',$id)->where('agent_id','=',$agent_id)->where('editable','=',1);
                    //         if(!$old_row){
                    //             $data = [
                    //                 'merchant_id' => $merchant_id,
                    //                 'product_id' => $id,
                    //                 'agent_id' => $agent_id,
                    //                 'agent_price' => $agent_pricex
                    //             ];
                    //             Product_price_agent::create($data);
                    //         }
                    //         $old_agent_price = Product_price_agent::where('product_id', '=',$id)->where('agent_id','=',$agent_id)->where('editable','=',1)->max('agent_price');
                    //         //checking old agent_price
                    //         if ($agent_pricex == $old_agent_price){

                    //         }else{
                    //             //change the editable of old product price agent
                    //             $editablechanger = [
                    //                 'editable' => 0
                    //             ];
                    //             $old_product_price_agent = $old_row->update($editablechanger);
                    //             //make new product price agent
                    //             $data = [
                    //                 'merchant_id' => $merchant_id,
                    //                 'product_id' => $id,
                    //                 'agent_id' => $agent_id,
                    //                 'agent_price' => $agent_pricex
                    //             ];
                    //             Product_price_agent::create($data);
                    //         };
                    //         $agent_price = array_splice($agent_price,2);
                    //     };
                    // };


                }else{ //ifpricechange

                    //1.updating editable old product
                    $dataeditable = [
                        'editable' => 0
                    ];
                    $oldproduct -> update($dataeditable);

                    //2.make new product
                    Product::create($newproductdata);

                    //3.collecting the id of new product
                    $newproduct_id = Product::max('id');

                    //4.updating stock
                    // $hasstock = $request->input('hasstock');
                    // if ($hasstock == 1)
                    // {
                    //     $update_stock = update_stock($request, $id, $product_id);
                    // };

                    // 5.updating formula
                    // $isformula = $request->input('isformula');
                    // if ($isformula == 1)
                    // {
                    //     $update_formula = update_formula($request, $id, $product_id);
                    // } else {

                    // };

                    //6.updating agent_price
                    // $agent_price = $request->input('agent_price');
                    // if ($agent_price)
                    // {
                    //     $agent_price = make_agentprice($request,$product_id);
                    // };
                };
                DB::commit();
                $out  = [
                    "message" => "UpdateProduct - Success",
                    "code"  => 200
                ];
                return response()->json($out, $out['code']);
            }catch (\exception $e) { //database tidak bisa diakses
                DB::rollback();
                $message = $e->getmessage();
                $out  = [
                    "message" => "$message"
                ];
                return response()->json($out,200);
            };
        };
    }

    public function index()
    {
        $listproduct = Product::leftjoin('product_stock', 'product_stock.product_id', '=', 'products.id')
        ->leftjoin('product_category', 'products.product_category_id', '=', 'product_category.id')
        ->addselect('products.id','products.name', 'products.price')
        ->addselect('discount')
        ->selectRaw('price - price*discount/100 as total_price')
        ->addselect(DB::raw('(CASE WHEN amount is null THEN null ELSE amount END) as total_stock'))
        ->addselect('product_category.information as category')
        ->addselect('products.information')
        ->where("products.editable", "=", "1")
        ->OrderBy("products.id", "ASC")
        ->get();

        $out = [
            "message" => "List Produk Sukses",
            "result" => $listproduct
        ];
        return response()->json($out, 200);
    }

    public function indexbycategoryid($categoryid)
    {
        $getPost = Product::leftjoin('product_stock', 'product_stock.product_id', '=', 'products.id')
        ->addselect('products.id','products.name as Nama')
        ->selectRaw('CONCAT("Rp ", price) as Harga')
        ->addselect('discount as Diskon')
        ->selectRaw('CONCAT("Rp ", price - price*discount/100) as Harga_Total')
        ->addselect(DB::raw('(CASE WHEN amount is null THEN "Tidak Ada" ELSE amount END) as Total_Stock'))
        ->where("products.editable", "=", "1")
        ->where("products.product_category_id","=",$categoryid)
        ->OrderBy("products.id", "ASC")
        ->get();

        $category = Product_category::where('id','=',$categoryid)->pluck("information");

        $out = [
            "message" => "List"." ".$category[0],
            "results" => $getPost
        ];
        return response()->json($out, 200);
    }

    public function indexalert()
    {
        $getPost = Product::leftjoin('product_stock', 'product_stock.product_id', '=', 'products.id')
        ->addselect('products.name','product_stock.amount')
        ->whereRaw('amount <= minimum_amount')
        ->where("products.editable", "=", "1")
        ->OrderBy("products.id", "ASC")
        ->get();

        //ke ingredient belom

        $out = [
            "message" => "List Produk",
            "results" => $getPost
        ];
        return response()->json($out, 200);
    }

    public function destroy($id)
    {
        $product =  Product::where('id','=',$id)->first();

        if (!$product) {
            $data = [
                "message" => "Product - NotFound"
            ];
        } else {
            $product->delete();
            $data = [
                "message" => "Delete - Product - Success"
            ];
        };
        return response()->json($data, 200);
    }
}
