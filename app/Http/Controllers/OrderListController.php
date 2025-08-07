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
use Illuminate\Support\Facades\Validator;

class OrderListController extends Controller //fix discount
{
    public function sameproductordereddetail($id)
    {
        $product_id = Order_list::where('order_list.product_id','=',$id)->first();
        $productsame_detail = Order_list::where('order_list.product_id','=',$id)
        ->leftjoin('products','products.id','=','order_list.product_id')
        ->leftjoin('orders','orders.id','=','order_list.order_id')
        ->leftjoin('tables','tables.id','=','orders.table_id')
        ->where('order_list_status_id','!=',4)
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
            Order::where('id','=',$order_id)->update($dataorder);

            //clearing old orderlist
            Order_list::where('order_id','=',$order_id)
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

        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first()], 200);
        }

        DB::beginTransaction();
        try {
            $user_id = $request->input('user_id');
            $new_status_id = $request->input('order_list_status_id');

            $orderlist = Order_list::find($orderlist_id);

            if (!$orderlist) {
                return response()->json(["message" => "OrderList not found"], 404);
            }

            $prev_status_id = $orderlist->order_list_status_id;

            // Update status
            $orderlist->update([
                'user_id' => $user_id,
                'order_list_status_id' => $new_status_id
            ]);

            // Cek jika baru diubah menjadi status "diproses/done" (id = 4), dan sebelumnya bukan 4
            if ($new_status_id == 4 && $prev_status_id != 4) {
                $product = Product::find($orderlist->product_id);

                if ($product) {
                    $amount = $orderlist->amount;

                    // Kurangi stok produk jika memiliki stok sendiri
                    if ($product->hasstock) {
                        Product_stock::where('product_id', $product->id)->decrement('amount', $amount);
                    }

                    // Kurangi bahan jika produk memiliki formula
                    if ($product->isformula) {
                        $formulas = Product_formula::where('product_id', $product->id)->get();
                        foreach ($formulas as $formula) {
                            $ingredientNeed = $formula->amount * $amount;
                            Ingredient_stock::where('ingredient_id', $formula->ingredient_id)->decrement('amount', $ingredientNeed);
                        }
                    }
                }
            }

            DB::commit();
            return response()->json(["message" => "OrderList - UpdateStatus - Success"], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(["message" => $e->getMessage()], 500);
        }
    }

    public function updateolsbyproductid(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|integer',
            'product_id' => 'required|integer'
        ]);

        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first()], 200);
        }

        DB::beginTransaction();
        try {
            $user_id = $request->input('user_id');
            $product_id = $request->input('product_id');

            // Ambil order list yang masih status awal (belum diproses)
            $orderLists = Order_list::where('product_id', $product_id)
                ->where('order_list_status_id', '!=', 4) // misal: 1 = pending
                ->get();

            foreach ($orderLists as $ol) {
                // Update status order list menjadi done (id = 4)
                $ol->update([
                    'user_id' => $user_id,
                    'order_list_status_id' => 4
                ]);

                // Kurangi stok produk dan bahan
                $product = Product::find($ol->product_id);
                $amount = $ol->amount;

                if ($product) {
                    if ($product->hasstock) {
                        Product_stock::where('product_id', $product->id)->decrement('amount', $amount);
                    }

                    if ($product->isformula) {
                        $formulas = Product_formula::where('product_id', $product->id)->get();
                        foreach ($formulas as $formula) {
                            $ingredientNeed = $formula->amount * $amount;
                            Ingredient_stock::where('ingredient_id', $formula->ingredient_id)->decrement('amount', $ingredientNeed);
                        }
                    }
                }
            }

            DB::commit();
            return response()->json(["message" => "OrderList - UpdateStatusByProductID - Success"], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(["message" => $e->getMessage()], 500);
        }
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
