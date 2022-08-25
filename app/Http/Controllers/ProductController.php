<?php

namespace App\Http\Controllers;

use App\Ingredient;
use App\Ingredient_stock;
use App\Product;
use App\Product_category;
use App\Product_formula;
use App\Product_price_vendor;
use App\Product_stock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; //pake facades DB
use Validator, Input, Redirect;

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
                'product_type_id' => 'required|integer',
                'product_category_id' => 'required|integer',
                'name' => 'required|unique:products',
                'price' => 'required|max:10',
                'discount' => 'required|max:4',
                'isformula' => 'required',
                'hasstock' => 'required',
                'information' => 'nullable|max:255',
                'amount' => 'nullable|integer',
                'minimum_amount' => 'nullable|integer',
                'ingredient' => 'nullable|array',
                'vendor_price' => 'nullable|array'
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
                $product_type_id = $request->input('product_type_id');
                $product_category_id = $request->input('product_category_id');
                $name = $request->input('name');
                $price = $request->input('price');
                $discount = $request->input('discount');
                $isformula = $request->input('isformula');        
                $dataproduct = [
                    'merchant_id' => $merchant_id,
                    'product_type_id' => $product_type_id,
                    'product_category_id' => $product_category_id,
                    'name' => $name,
                    'price' => $price,
                    'discount' => $discount,
                    'editable' => 1,
                    'isformula' => $isformula
                ];
                Product::create($dataproduct);
                $newproductid = Product::max("id");

                //making stock
                $hasstock = $request->input('hasstock');
                if ($hasstock == 1) 
                {        
                    $amount = $request->input('amount');
                    $minimum_amount = $request->input('minimum_amount'); 
                    $datastock = [
                        'merchant_id' => $merchant_id,
                        'product_id' => $newproductid,
                        'amount' => $amount,
                        'minimum_amount' => $minimum_amount
                    ];
                    Product_stock::create($datastock);
                };

                //making formula
                $isformula = $request->input('isformula');
                if ($isformula == 1) 
                {
                    $ingredient = $request->input('ingredient');        
                    $total_ingredientarray = count($ingredient) / 2;
                    for ($i=0; $i<$total_ingredientarray; $i++)
                    {
                        $dataformula =[
                            'product_id' => $newproductid,
                            'ingredient_id' => $ingredient[0],
                            'amount' => $ingredient[1]
                            ];
                        Product_formula::create($dataformula); 
                        $ingredient = array_splice($ingredient,2);
                    };
                };

                //making vendor_price
                $vendor_price = $request->input('vendor_price');
                if ($vendor_price) 
                {
                    $merchant_id = $request->input('merchant_id');
                    $vendor_price = $request->input('vendor_price');
                    $total_vendorpricearray = count($vendor_price) / 2;
                    for ($i=0; $i<$total_vendorpricearray; $i++) {
                        $datavendorprice = [
                            'merchant_id' => $merchant_id,
                            'product_id' => $newproductid,
                            'vendor_id' => $vendor_price[0],
                            'vendor_price' => $vendor_price[1]
                        ];
                        Product_price_vendor::create($datavendorprice);
                        $vendor_price = array_splice($vendor_price, 2);
                    };
                };

                DB::commit();
                $out  = [
                    "message" => "Produk Telah Dibuat"
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
        if ($request->isMethod('patch')) 
        {
            //validasi
            $validator = Validator::make($request->all(), 
            [
                'merchant_id' => 'required|integer',
                'partner_id' => 'required|integer',
                'product_category_id' => 'required|integer',
                'name' => 'required',
                'price' => 'required|max:10',
                'discount' => 'required|max:4',
                'isformula' => 'required',
                'hasstock' => 'required',
                'information' => 'nullable|max:255',
                'amount' => 'nullable|integer',
                'minimum_amount' => 'nullable|integer',
                'ingredient' => 'nullable|array',
                'vendor_price' => 'nullable|array'
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
                //getting old product record
                $merchant_id = $request->input('merchant_id');
                $oldproduct = Product::where('id','=',$id);
                $oldproductprice = Product::where('id','=',$id)->max('price');

                //checking the price
                $price = $request->input('price');
                if($price == $oldproductprice)
                { 
                    //1.update old product
                    $product = update_product($request, $id);

                    //2.updating product_stock
                    $hasstock = $request->input('hasstock');
                    if ($hasstock == 1) 
                    {
                        update_stock($request,$id,$id);
                    };

                    //3.updating formula
                    $isformula = $request->input('isformula');
                    if ($isformula == 1) 
                    {      
                        update_formula($request,$id,$id);
                    };

                    //4.updating vendor_price
                    $vendor_price = $request -> input('vendor_price');
                    if ($vendor_price){
                        $vendor_pricecount = count($vendor_price) / 2;
                        for ($i=0;$i<$vendor_pricecount; $i++){
                            $vendor_id = $vendor_price[0];
                            $vendor_pricex = $vendor_price[1];
                            $old_row = Product_price_vendor::where('product_id','=',$id)->where('vendor_id','=',$vendor_id)->where('editable','=',1);
                            if(!$old_row){
                                $data = [
                                    'merchant_id' => $merchant_id,
                                    'product_id' => $id,
                                    'vendor_id' => $vendor_id,
                                    'vendor_price' => $vendor_pricex
                                ];
                                Product_price_vendor::create($data);
                            }
                            $old_vendor_price = Product_price_vendor::where('product_id', '=',$id)->where('vendor_id','=',$vendor_id)->where('editable','=',1)->max('vendor_price');
                            //checking old vendor_price
                            if ($vendor_pricex == $old_vendor_price){
                                
                            }else{
                                //change the editable of old product price vendor
                                $editablechanger = [
                                    'editable' => 0
                                ];
                                $old_product_price_vendor = $old_row->update($editablechanger);
                                //make new product price vendor
                                $data = [
                                    'merchant_id' => $merchant_id,
                                    'product_id' => $id,
                                    'vendor_id' => $vendor_id,
                                    'vendor_price' => $vendor_pricex
                                ];
                                Product_price_vendor::create($data);
                            };
                            $vendor_price = array_splice($vendor_price,2);
                        };
                    };
                

                }else{ //ifpricechange               
                    
                    //1.updating editable old product
                    $dataeditable = [
                        'editable' => 0
                    ];
                    $updateoldproduct = $oldproduct -> update($dataeditable);

                    //2.make new product
                    $product = make_product($request);

                    //3.collecting the id of new product
                    $product_id = Product::max('id');

                    //4.updating stock
                    $hasstock = $request->input('hasstock');
                    if ($hasstock == 1) 
                    {
                        $update_stock = update_stock($request, $id, $product_id);                    
                    };
                    
                    // 5.updating formula
                    $isformula = $request->input('isformula');
                    if ($isformula == 1) 
                    {
                        $update_formula = update_formula($request, $id, $product_id);
                    } else {
                        
                    };
                    
                    //6.updating vendor_price
                    $vendor_price = $request->input('vendor_price');
                    if ($vendor_price) 
                    {
                        $vendor_price = make_vendorprice($request,$product_id);
                    };
                };
                DB::commit();
                $out  = [
                    "message" => "success_update_data",
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
        $listproduct = Product::leftjoin('product_stock', 'product_stock.product_id', '=', 'products.id')
        ->leftjoin('product_category', 'products.product_category_id', '=', 'product_category.id')
        ->addselect('products.id','products.name', 'products.price')
        ->addselect('discount')
        ->selectRaw('price - price*discount/100 as total_price')
        ->addselect(DB::raw('(CASE WHEN amount is null THEN null ELSE amount END) as total_stock'))
        ->addselect('product_category.information as category') 
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
