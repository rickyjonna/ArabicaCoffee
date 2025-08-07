<?php

namespace App\Http\Controllers;

use App\Invoice;
use Carbon\Carbon;
use App\Order;
use App\Order_list;
use App\Product;
use App\Income;
use App\Table;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; //pake facades DB
use Illuminate\Support\Facades\Validator;

class InvoiceController extends Controller
{
    public function insertinvoice(Request $request)
    {
        if ($request->isMethod('post')) {

        $validator = Validator::make($request->all(), [
            'merchant_id' => 'required|integer',
            'order_id' => 'required|integer',
            'user_id' => 'required|integer',
            'payment_id' => 'required|integer',
            'discount' => 'required|integer',
            'tax' => 'required|integer',
            'phone_number' => 'nullable|max:20',
            'email' => 'nullable|max:20'
        ]);

        if ($validator->fails()) {
            return response()->json([
                "message" => $validator->errors()->first()
            ], 422);
        }

        $merchant_id = $request->input('merchant_id');
        $order_id = $request->input('order_id');
        $user_id = $request->input('user_id');
        $payment_id = $request->input('payment_id');
        $discount = $request->input('discount');
        $tax = $request->input('tax');
        $phone_number = $request->input('phone_number');
        $email = $request->input('email');

        DB::beginTransaction();
        try {
            // 🔒 Kunci logika atomik untuk hindari double klik
            $existing = Invoice::where('order_id', $order_id)
                ->where('status', 'UNPAID')
                ->lockForUpdate() // 🔒 lock baris ini selama transaksi
                ->first();

            if ($existing) {
                // Hapus invoice UNPAID sebelumnya sebelum buat yang baru
                $existing->delete();
            }

            // Hitung total harga dari order list
            $totalprice = Order_list::where('order_list.order_id', '=', $order_id)
                ->leftJoin('products', 'products.id', '=', 'order_list.product_id')
                ->sum(DB::raw('(products.price - (products.price * products.discount / 100)) * order_list.amount'));

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

            $invoice = Invoice::create($data);
            $invoice_id = $invoice->id;
            $invoice_total = $totalprice - $discount + $tax;

            DB::commit();

            return response()->json([
                "message" => "Invoice Berhasil Dibuat",
                "results" => [
                    "invoice_id" => $invoice_id,
                    "invoice_total" => $invoice_total
                ]
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                "message" => "Terjadi kesalahan: " . $e->getMessage()
            ], 500);
        }
    }

    return response()->json([
        "message" => "Metode tidak diizinkan"
    ], 405);
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

            //ubah status table
            $datatable = [
                'status' => "Available"
            ];
            $order = Order::where('id','=',$invoice->max('order_id'));
            $table = Table::where('id','=',$order->max('table_id'));
            $updatetablestatus = $table->update($datatable);

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

    public function indextoday() {
        $today = Carbon::now('Asia/Jakarta')->toDateString();
        $invoices = Invoice::with('order.orderLists.product')
        ->whereDate('created_at', $today)
        ->get();
        return response()->json($invoices);
    }
    
}//endclass
