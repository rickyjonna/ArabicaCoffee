<?php

namespace App\Http\Controllers;

use App\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; //pake facades DB
use Validator, Input, Redirect;

class PaymentController extends Controller
{
    public function insertpayment(Request $request)
    {
        if ($request->isMethod('post')) 
        {
            $validator = Validator::make($request->all(), 
            [
                'information' => 'required|max:30',
                'discount' => 'required|max:4'
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
                $discount = $request->input('discount');           

                //making vendor
                $data = [
                    'information' => $information,
                    'discount' => $discount
                ];
                $insert = Payment::create($data);
                DB::commit();
                $out  = [
                    "message" => "InsertPayment - Success",
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
        $getPost = payment::select('information as Jenis_Pembayaran', 'discount as Diskon_Pembayaran')
        ->OrderBy("payments.id", "ASC")
        ->get();

        $out = [
            "message" => "List Tipe Pembayaran",
            "results" => $getPost,
            "code" => 200
        ];
        return response()->json($out, $out['code']);
    }

    public function updatepayment($id, Request $request)
    {
        if ($request->isMethod('post')) 
        {
            $validator = Validator::make($request->all(), 
            [
                'information' => 'required|max:30',
                'discount' => 'required|max:4'
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
                $discount = $request->input('discount');   

                //updating payment
                $oldpayment = Payment::where('id','=',$id);
                $data = [
                    'information' => $information,             
                    'discount' => $discount            
                ];
                $updatepayment = $oldpayment -> update($data);
                DB::commit();
                $out  = [
                    "message" => "EditPayment - Success",
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
        $payment =  Payment::where('id','=',$id)->first();

        if (!$payment) {
            $data = [
                "message" => "error / data not found",
                "code" => 200
            ];
        } else {
            $payment->delete();
            $data = [
                "message" => "DeletePayment - Success",
                "code" => 200
            ];
        };
        return response()->json($data, $data['code']);
    }
}