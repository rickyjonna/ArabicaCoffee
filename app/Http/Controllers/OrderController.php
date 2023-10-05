<?php

namespace App\Http\Controllers;

use App\Ingredient;
use App\Ingredient_stock;
use App\User;
use App\Order;
use App\Table;
use App\Product;
use App\Product_stock;
use App\Order_list;
use App\Product_formula;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB; //pake facades DB

class OrderController extends Controller
{
    public function insertorder(Request $request)
    {
        if ($request->isMethod('post'))
        {
            $validator = Validator::make($request->all(),
            [
                'token' => 'required',
                'merchant_id' => 'required|integer',
                'product_id' => 'required|array',
                'amount' => 'required|array',
                'table_id' => 'nullable|integer',
                'agent_id' => 'nullable|integer',
                'information' => 'nullable',
                'note' => 'nullable'
            ]);
            $messages = $validator->errors();
            if ($validator->fails())
            {
                //request tidak sempurna (ada yang kosong)
                $out = [
                    "message" => $messages->first()
                ];
                return response()->json($out, 200);
            };

            DB::beginTransaction();
            try{
                //initialize
                $token = $request->input('token');
                $user_id = User::where('token','=',$token)->max('id');
                $merchant_id = $request->input('merchant_id');
                $table_id = $request->input('table_id');
                $agent_id = $request->input('agent_id');
                $information = $request->input('information');
                $product_id = $request->input('product_id');
                $product_id_count = count($product_id);
                $amount = $request->input('amount');
                $note = $request->input('note');

                //changing table status
                $newstatus = [
                    'status' => 'NotAvailable'
                ];
                $tablestatus = Table::where('id','=',$table_id)
                ->update($newstatus);

                //making Order
                $data = [
                    'merchant_id' => $merchant_id,
                    'table_id' => $table_id,
                    'user_id' => $user_id,
                    'agent_id' => $agent_id,
                    'status' => "OPEN",
                    'information' => $information,
                    'note' => $note
                ];
                Order::create($data);

                //get the order_id
                $order_id = Order::max('id');

                //checking if product has stock then (-)
                for($i=0; $i < $product_id_count; $i++){
                    $product = Product::where('id','=',$product_id[$i])->first();
                    if($product->max('hasstock') == 1){
                        //old amount
                        $product_stock_amount = Product_stock::where('product_id','=',$product_id)->max('amount');
                        if($product_stock_amount >= $amount[$i]){
                            //new amount
                            $newstock = $product_stock_amount - $amount[$i];
                            $datastock = [
                                'amount' => $newstock
                            ];
                            //updating the stock
                            Product_stock::where('product_id','=',$product_id)->update($datastock);
                        } else {
                            DB::rollback();
                                $out  = [
                                    "message" => "Stok Tidak Mencukupi untuk ".$product->name
                                ];
                                return response()->json($out,200);
                        }
                    };
                }

                //checking if product has ingredient then (-)
                for($i=0; $i < $product_id_count; $i++){
                    $product = Product::where('id','=',$product_id[$i])->first();
                    if($product->max('isformula') == 1){
                        $formulas = Product_formula::where('product_id','=',$product_id[$i])->get();
                        foreach ($formulas as $formula) {
                            $ingredient = Ingredient::where('id','=',$formula->ingredient_id)->first();
                            $ingredient_stock = Ingredient_stock::where('ingredient_id','=',$ingredient->id)->first();
                            if ($ingredient_stock->amount >= ($amount[$i]*$formula->amount)) {
                                $ingredient_stock_newamount = $ingredient_stock->amount -= ($amount[$i]*$formula->amount);
                                $data = [
                                    'amount' => $ingredient_stock_newamount,
                                ];
                                Ingredient_stock::where('ingredient_id','=',$ingredient->id)->update($data);
                            } else {
                                DB::rollback();
                                $out  = [
                                    "message" => "Stok Tidak Mencukupi untuk ".$product->name
                                ];
                                return response()->json($out,200);
                            }
                        }
                    };
                }

                //making Order list
                for($i=0; $i < $product_id_count; $i++)
                {
                    $data = [
                        'order_id' => $order_id,
                        'merchant_id' => $merchant_id,
                        'product_id' => $product_id[$i],
                        'user_id' => $user_id,
                        'order_list_status_id' => 1,
                        'amount' => $amount[$i]
                    ];
                    Order_list::create($data);
                };

                DB::commit();
                $out  = [
                    "message" => "Order Telah Dibuat"
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

    public function index()
    {
        $table = Order::leftjoin('tables', 'tables.id', '=', 'orders.table_id')
        ->where('orders.status', "=", "OPEN")
        ->where('orders.table_id','!=',null)
        ->OrderBy("orders.id", "ASC")
        ->addselect('tables.id as id')
        ->addselect('tables.number','tables.extend')
        ->addselect('orders.id as order_id')
        ->addselect('orders.note as order_note')
        ->get();
        $gojek = Order::where('orders.status', "=", "OPEN")
        ->where('orders.agent_id','=',2)
        ->leftjoin('agents','agents.id','=','orders.agent_id')
        ->orderby('orders.id',"ASC")
        ->addselect('information')
        ->addselect('orders.id as order_id')
        ->addselect('orders.note as order_note')
        ->get();
        $grab = Order::where('orders.status', "=", "OPEN")
        ->where('orders.agent_id','=',3)
        ->leftjoin('agents','agents.id','=','orders.agent_id')
        ->orderby('orders.id',"ASC")
        ->addselect('information')
        ->addselect('orders.id as order_id')
        ->addselect('orders.note as order_note')
        ->get();
        $takeaway = Order::where('orders.status', "=", "OPEN")
        ->where('orders.agent_id','=',1)
        ->where('orders.information','!=',null)
        ->addselect('information')
        ->addselect('orders.id as order_id')
        ->addselect('orders.note as order_note')
        ->get();

        $out = [
            "table" => $table,
            "gojek" => $gojek ,
            "grab" => $grab,
            "take_away" => $takeaway
        ];
        return response()->json($out, 200);
    }
}
