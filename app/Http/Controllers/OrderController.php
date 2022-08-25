<?php

namespace App\Http\Controllers;

use App\Order;
use App\Order_list;
use App\Table;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; //pake facades DB
use Validator, Input, Redirect;

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
                'table_id' => 'nullable|integer|unique:orders',
                'vendor_id' => 'nullable|integer',
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
                $vendor_id = $request->input('vendor_id');  
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
                    'vendor_id' => $vendor_id,
                    'status' => "OPEN",
                    'information' => $information,
                    'note' => $note
                ];
                $insert = Order::create($data);

                //get the order_id
                $order_id = Order::max('id');

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
                    $insert = Order_list::create($data);
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
        // masih 2 vendor
        $gojek = Order::where('orders.status', "=", "OPEN")
        ->where('orders.vendor_id','=',2)
        ->leftjoin('vendors','vendors.id','=','orders.vendor_id')       
        ->orderby('orders.id',"ASC")
        ->addselect('information')
        ->addselect('orders.id as order_id')
        ->addselect('orders.note as order_note')
        ->get();
        $grab = Order::where('orders.status', "=", "OPEN")
        ->where('orders.vendor_id','=',1)
        ->leftjoin('vendors','vendors.id','=','orders.vendor_id')       
        ->orderby('orders.id',"ASC")
        ->addselect('information')
        ->addselect('orders.id as order_id')
        ->addselect('orders.note as order_note')
        ->get();
        $takeaway = Order::where('orders.status', "=", "OPEN")
        ->where('orders.vendor_id','=',null)
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