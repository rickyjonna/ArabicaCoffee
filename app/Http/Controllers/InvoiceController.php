<?php

namespace App\Http\Controllers;

use App\Invoice;
use App\Order;
use App\Order_list;
use App\Product;
use App\Income;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; //pake facades DB
use Validator, Input, Redirect;

class InvoiceController extends Controller
{
    public function insertinvoice(Request $request)
    {
        if ($request->isMethod('post')) 
        {
            $validator = Validator::make($request->all(),  [
                'merchant_id' => 'required|integer',
                'order_id' => 'required|integer',
                'user_id' => 'required|integer',
                'payment_id' => 'required|integer',
                'discount' => 'required|integer',
                'tax' => 'required|integer',
                'phone_number' => 'nullable|max:20',
                'email' => 'nullable|max:20'
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
            try{
                //initialize
                $merchant_id = $request->input('merchant_id');
                $order_id = $request->input('order_id');         
                $user_id = $request->input('user_id'); 
                $payment_id = $request->input('payment_id');  
                $discount = $request->input('discount');  
                $tax = $request->input('tax'); 
                $phone_number = $request->input('phone_number');
                $email = $request->input('email');
                $totalprice = Order_list::where('order_list.order_id','=',$order_id) 
                ->leftjoin('products','products.id','=','order_list.product_id')
                ->sum(DB::raw('(products.price - (products.price * products.discount / 100)) * order_list.amount'));

                //clearing old invoice
                $delete_oldinvoice = Invoice::where('order_id','=',$order_id)
                ->where('status','=','UNPAID')
                ->delete();

                //making invoice
                $data = [
                    'merchant_id' => $merchant_id,
                    'order_id' => $order_id,
                    'user_id' => $user_id,
                    'payment_id' => $payment_id,
                    'discount' => $discount,
                    'tax' => $tax,
                    'total' => $totalprice,
                    'status' => "UNPAID",
                    'phone_number' => $phone_number,
                    'email' => $email
                ];
                $insert = Invoice::create($data);
                
                //get invoice id
                $invoice_id =Invoice::max('id');
                $invoicetotal = $totalprice - $discount + $tax;
                $results = [
                    "invoice_id" => $invoice_id,
                    "invoice_total" => $invoicetotal
                ];

                DB::commit();
                $out  = [
                    "message" => "Invoice Berhasil Dibuat",
                    "results" => $results
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

    public function checkinvoice($invoice_id)
    {
        $invoice = Invoice::where('id','=',$invoice_id)
        ->select('order_id','user_id','payment_id','status','discount','tax','total','phone_number','email')
        ->get();

        $out = [
            "message" => "CheckInvoice($invoice_id) - Success",
            "result" => $invoice
        ];
        return response()->json($out, 200);
    }

    public function checkout($invoice_id, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'information' => 'nullable'                
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
            //ubah status invoice
            $datainvoice = [
                'status' => "PAID"
            ];
            $invoice = Invoice::where('id','=',$invoice_id);
            $updateinvoice = $invoice->update($datainvoice);
            //ubah status order
            $dataorder = [
                'status' => 'CLOSED'
            ];
            $order_id = $invoice->max('order_id');
            $order = Order::where('id','=',$order_id);
            $updateorder = $order->update($dataorder);

            //buat laporan pemasukan
            $merchant_id = $invoice->max('merchant_id');
            $invoicetotal = $invoice->max('total');
            $invoicediscount = $invoice->max('discount');
            $invoicetax = $invoice->max('tax');
            $invoice_paymentdiscount = Invoice::where('invoices.id','=',$invoice_id)
            ->leftjoin('payments','payments.id','=','invoices.payment_id')
            ->max('payments.discount');
            $incometotal = $invoicetotal - $invoicediscount + $invoicetax - ($invoicetotal * $invoice_paymentdiscount / 100);
            $information = $request->input('information');
            $dataincome = [
                'merchant_id' => $merchant_id,
                'income_type_id' => 1,
                'invoice_id' => $invoice_id,
                'total' => $incometotal,
                'information' => $information
            ];
            $income = Income::create($dataincome);

            DB::commit();
            $out = [
                'message' => 'Checkout - Success'
            ];                        
            return response() ->json($out,200);
        }catch (\exception $e) { //database tidak bisa diakses
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
        $invoice =  invoice::where('id','=',$id)->first();

        if (!$invoice) {
            $data = [
                "message" => "error / data not found",
                "data" => 404
            ];
        } else {
            $invoice->delete();
            $data = [
                "message" => "success deleted",
                "data" => 200
            ];
        };
        return response()->json($data, 200);
    }
}//endclass