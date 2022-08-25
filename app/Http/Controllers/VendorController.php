<?php

namespace App\Http\Controllers;

use App\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; //pake facades DB
use Validator, Input, Redirect;

class VendorController extends Controller
{
    public function insertvendor(Request $request)
    {
        if ($request->isMethod('post')) 
        {
            $validator = Validator::make($request->all(), 
            [
                'payment_id' => 'required|integer',
                'name' => 'required|unique:vendors|max:30',
                'percentage' => 'required|max:4'
                
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
                $payment_id = $request->input('payment_id');
                $name = $request->input('name');
                $percentage = $request->input('percentage'); //janganlupa  ubah  diskon ke bentuk non %            

                //making vendor
                $data = [
                    'payment_id' => $payment_id,
                    'name' => $name,             
                    'percentage' => $percentage            
                ];
                $insert = Vendor::create($data);
                DB::commit();
                $out  = [
                    "message" => "InsertAgent - Success",
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

    public function updatevendor($id, Request $request)
    {
        if ($request->isMethod('post')) 
        {
            $validator = Validator::make($request->all(), 
            [
                'payment_id' => 'required|integer',
                'name' => 'required|max:30',
                'percentage' => 'required|max:4'
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
                $payment_id = $request->input('payment_id');
                $name = $request->input('name');
                $percentage = $request->input('percentage');   

                //updating old vendor
                $oldvendor = Vendor::where('id','=',$id);
                $data = [
                    'payment_id' => $payment_id,
                    'name' => $name,             
                    'percentage' => $percentage            
                ];
                $insertvendor = $oldvendor -> update($data);
                DB::commit();
                $out  = [
                    "message" => "EditAgent - Success",
                    "code"  => 200
                ];
                return response()->json($out,$out['code']);
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
        $getPost = Vendor::leftjoin('payments', 'payments.id', '=', 'vendors.payment_id')
        ->addselect('vendors.name as Nama_Agen', 'percentage as Komisi', 'payments.information as Metode_Pembayaran') 
        ->OrderBy("vendors.id", "ASC")
        ->get();

        $out = [
            "message" => "List Agen",
            "results" => $getPost,
            "code" => 200
        ];
        return response()->json($out, $out['code']);
    }

    public function destroy($id)
    {
        $vendor =  Vendor::where('id','=',$id)->first();

        if (!$vendor) {
            $data = [
                "message" => "error / data not found",
                "code" => 200
            ];
        } else {
            $vendor->delete();
            $data = [
                "message" => "DeleteAgent - Success",
                "code" => 200
            ];
        };
        return response()->json($data, $data['code']);
    }
}