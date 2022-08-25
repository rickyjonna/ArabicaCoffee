<?php

namespace App\Http\Controllers;

use App\Partner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; //pake facades DB
use Validator, Input, Redirect;

class PartnerController extends Controller
{
    public function insertpartner(Request $request)
    {
        if ($request->isMethod('post')) 
        {
            $validator = Validator::make($request->all(), 
            [
                'owner' => 'required|unique:partners|max:30',
                'profit' => 'required|max:4'                
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
                $owner = $request->input('owner');
                $profit = $request->input('profit');       //janganlupa  ubah  profit ke bentuk non %    

                //making partner
                $data = [
                    'owner' => $owner,
                    'profit' => $profit         
                ];
                $insert = Partner::create($data);
                DB::commit();
                $out  = [
                    "message" => "InsertPartner - Success",
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
        }
    }

    public function index()
    {
        $getPost = Partner::Select('owner as Rekan', 'profit as Profit') 
        ->OrderBy("id", "DESC")
        ->get();

        $out = [
            "message" => "List Rekan",
            "results" => $getPost,
            "code" => 200
        ];
        return response()->json($out, $out['code']);
    }

    public function updatepartner($id, Request $request)
    {
        if ($request->isMethod('post')) 
        {
            $validator = Validator::make($request->all(), 
            [
                'owner' => 'required|max:30',
                'profit' => 'required|max:4' 
            ]);
            $messages = $validator->errors();
            if ($validator->fails()) {
                $out = [
                    "message" => $messages->first(),
                    "code"   => 200
                ];
            return response()->json($out, $out['code']);
            };

            DB::beginTransaction();
            try {
                //initialize
                $owner = $request->input('owner');
                $profit = $request->input('profit'); 

                //updating old partner
                $oldpartner = Partner::where('id','=',$id);
                $data = [
                    'owner' => $owner,
                    'profit' => $profit
                ];
                $updatepartner = $oldpartner -> update($data);
                DB::commit();
                $out  = [
                    "message" => "EditPartner - Success",
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
        $partner =  Partner::where('id','=',$id)->first();

        if (!$partner) {
            $data = [
                "message" => "error / data not found",
                "code" => 200
            ];
        } else {
            $partner->delete();
            $data = [
                "message" => "DeletePartner - Success",
                "code" => 200
            ];
        };
        return response()->json($data, $data['code']);
    }

}