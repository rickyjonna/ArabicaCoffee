<?php

namespace App\Http\Controllers;

use App\Order;
use App\Order_list;
use App\Product;
use App\Product_stock;
use App\Product_formula;
use App\Ingredient;
use App\Ingredient_stock;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; //pake facades DB
use Validator, Input, Redirect;

class OrderListController extends Controller //fix discount
{
    public function sameproductordereddetail($id)
    {
        $product_id = Order_list::where('order_list.product_id','=',$id)->first();
        $productsame_detail = Order_list::where('order_list.product_id','=',$id)
        ->leftjoin('products','products.id','=','order_list.product_id')
        ->leftjoin('orders','orders.id','=','order_list.order_id')
        ->leftjoin('tables','tables.id','=','orders.table_id')
        ->where('orders.status','=','OPEN')
        ->select('products.id as product_id','order_list.id as orderlist_id','orders.id as order_id','tables.id as table_id','tables.number as table_number','tables.extend as table_extend','orders.information as order_information','order_list.amount as total')   
        ->get();    

        if($product_id){
            $out = [
                "message" => "SameProductDetail - Success",
                "results" => $productsame_detail
            ];
        }else{
            $out = [
                "message" => "Product Not Found"
            ];
        }
        return response()->json($out, 200);
    }

    public function updateorderlist(Request $request)
    {
        $validator = Validator::make($request->all(), 
        [
            'token' => 'required',
            'order_id' => 'required|integer',
            'note' => 'nullable',
            'product_id' => 'nullable|array',
            'amount' => 'nullable|array',
            'order_list_status_id' => 'nullable|array'
        ]);
        $messages = $validator->errors();
        if ($validator->fails()) 
        {
            //request tidak sempurna (ada yang kosong)
            $out = [
                "message" => $messages->first(),
                "code"   => 200
            ];
            return response()->json($out, $out['code']);
        };

        DB::beginTransaction();
        try{
            //initialize
            $token = $request->input('token'); 
            $user_id = User::where('token','=',$token)->max('id');
            $order_id = $request->input('order_id');
            $note = $request->input('note');
            $product_id = $request->input('product_id');
            $product_id_count = count($product_id); 
            $amount = $request->input('amount');
            $order_list_status_id = $request->input('order_list_status_id');

            //updating order
            $dataorder = [
                'user_id' => $user_id,
                'note' => $note
            ];
            $updateorder = Order::where('id','=',$order_id)->update($dataorder);

            //clearing old orderlist
            $order_list = Order_list::where('order_id','=',$order_id)
            ->where('order_list_status_id','!=',4)
            ->delete();

            //making new orderlist
            for($i=0; $i < $product_id_count; $i++) 
            {
                $data = [
                    'order_id' => $order_id,
                    'merchant_id' => 1,
                    'user_id' => $user_id,
                    'product_id' => $product_id[$i],
                    'amount' => $amount[$i],
                    'order_list_status_id' => $order_list_status_id[$i]
                ];
                $insert = Order_list::create($data);
            };

            //change order status when every orderlist done
            $orderlistnotdone = Order_list::where('order_id','=',$order_id)->where('order_list_status_id','!=',4)->max('id');
            if(!$orderlistnotdone){
                $dataorder = [
                    'status' => 'CLOSED'
                ];
                Order::where('id','=',$order_id)->update($dataorder);
            } 
            

            DB::commit();
            $out  = [
                "message" => "Update - OrderList - Success"
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
    }

    public function updateorderliststatus($orderlist_id, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer',
            'order_list_status_id' => 'required|integer'
        ]);
        $messages = $validator->errors();
        if ($validator->fails()) 
        {
            $out = [
                "message" => $messages->first()
            ];
            return response()->json($out, 200);
        };
        DB::beginTransaction();
        try {
            $user_id = $request->input('user_id');
            $order_id = Order_list::where('id','=',$orderlist_id)->max('order_id');
            $product_id = Order_list::where('id','=',$orderlist_id)->max('product_id');
            $order_list_amount = Order_list::where('id','=',$orderlist_id)->max('amount');
            $orderlistlist =  Order_list::where('id','=',$orderlist_id)->first();
    
            if (!$orderlistlist) {
                $data = [
                    "message" => "error / data not found"
                ];
            } else {
                //updating status id
                $order_list_status_id = $request -> input('order_list_status_id');
                $neworderlistdata = [
                    'user_id' => $user_id,
                    'order_list_status_id' => $order_list_status_id
                ];
                Order_list::where('id','=',$orderlist_id)->update($neworderlistdata);

                //checking if served -> then stock (-)
                if($order_list_status_id == 4){
                    if(Product::where('id','=',$product_id)->max('isformula') == 0){
                        //old amount
                        $product_stock_amount = Product_stock::where('product_id','=',$product_id)->max('amount');
                        //new amount
                        $newstock = $product_stock_amount - $order_list_amount;
                        $datastock = [
                            'amount' => $newstock
                        ];
                        //updating the stock
                        Product_stock::where('product_id','=',$product_id)->update($datastock);
                    }
                };

                //checking other otherlist with same orderid
                $orderlistnotdone = Order_list::where('order_id','=',$order_id)
                ->where('order_list_status_id','!=',4)
                ->first();
                if(!$orderlistnotdone){
                    $neworderstatus = [
                        'status' => 'CLOSED'
                        ];
                    Order::where('id','=',$order_id)->update($neworderstatus);
                };
            };
            DB::commit();
            $data = [
                "message" => "OrderList - UpdateStatus - Success"
            ];
            return response()->json($data, 200);
        } catch (\exception $e) {
            DB::rollback();
            $message = $e->getmessage();
            $out  = [
                "message" => $message
            ];  
            return response()->json($out,200);
        };          
    }

    public function updateolsbyproductid(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer',
            'product_id' => 'required|integer'
        ]);
        $messages = $validator->errors();
        if ($validator->fails()) 
        {
            $out = [
                "message" => $messages->first()
            ];
            return response()->json($out, 200);
        };
        DB::beginTransaction();
        try {
            $product_id = $request->input('product_id');
            $user_id = $request->input('user_id');
            $neworderlistdata = [
                "user_id" => $user_id,
                "order_list_status_id" => 2
            ];
            $order_list = Order_list::where('product_id','=',$product_id)
            ->where('order_list_status_id','=',1)
            ->update($neworderlistdata);

            DB::commit();
            $data = [
                "message" => "OrderList - UpdateStatusByProductID - Success"
            ];
            return response()->json($data, 200);
        } catch (\exception $e) {
            DB::rollback();
            $message = $e->getmessage();
            $out  = [
                "message" => $message
            ];  
            return response()->json($out,200);
        };          
    }

    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $order_id = Order_list::where('id','=',$id)->max('order_id');
            $orderlistlist =  Order_list::where('id','=',$id)->first();
    
            if (!$orderlistlist) {
                $data = [
                    "message" => "error / data not found"
                ];
            } else {
                $orderlistlist->delete();
                $orderlistnotdone = Order_list::where('order_id','=',$order_id)
                ->where('order_list_status_id','!=',4)
                ->first();
                if(!$orderlistnotdone){
                    $data = [
                        'status' => 'CLOSED'
                        ];
                    Order::where('id','=',$order_id)->update($data);
                };
                $data = [
                    "message" => "OrderList - Delete - Success"
                ];
            };
            DB::commit();
            return response()->json($data, 200);
        } catch (\exception $e) {
            DB::rollback();
            $message = $e->getmessage();
            $out  = [
                "message" => $message
            ];  
            return response()->json($out,200);
        };      
    }
}