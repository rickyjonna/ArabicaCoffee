<?php

namespace App\Http\Controllers;

use App\Table;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; //pake facades DB
use Illuminate\Support\Facades\Validator;

class TableController extends Controller
{
    public function inserttable(Request $request)
    {
        if ($request->isMethod('post'))
        {
            $validator = Validator::make($request->all(), [
                'number' => 'required|integer',
                'extend' => 'required|integer',
                'status' => 'required'
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
                $number = $request->input('number');
                $extend = $request->input('extend');
                $status = $request->input('status');

                //making Table
                $data = [
                    'merchant_id' => 1,
                    'number' => $number,
                    'extend' => $extend,
                    'status' => $status
                ];
                Table::create($data);
                DB::commit();
                $out  = [
                    "message" => "InsertTable - Success",
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

    public function updatetable($id, Request $request)
    {
        if ($request->isMethod('post'))
        {
            $validator = Validator::make($request->all(),
            [
                'number' => 'required|integer',
                'extend' => 'required|integer',
                'status' => 'required'
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
                $number = $request->input('number');
                $extend = $request->input('extend');
                $status = $request->input('status');

                //updating old Table
                $oldtable = Table::where('id','=',$id);
                $data = [
                    'number' => $number,
                    'extend' => $extend,
                    'status' => $status
                ];
                $updatetable = $oldtable -> update($data);
                DB::commit();
                $out  = [
                    "message" => "EditTable - Success",
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
        $getPost = Table::Select('id','number', 'extend', 'status')
        ->OrderBy("number", "ASC")
        ->get();

        $out = [
            "message" => "Table List - Success",
            "results" => $getPost
        ];
        return response()->json($out, 200);
    }

    public function destroy($id)
    {
        $table =  Table::where('id','=',$id)->first();

        if (!$table) {
            $data = [
                "message" => "error / data not found",
                "code" => 200
            ];
        } else {
            $table->delete();
            $data = [
                "message" => "DeleteTable - Success",
                "code" => 200
            ];
        };
        return response()->json($data, $data['code']);
    }
}
