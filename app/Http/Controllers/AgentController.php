<?php

namespace App\Http\Controllers;

use App\Agent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; //pake facades DB
use Illuminate\Support\Facades\Validator;

class AgentController extends Controller
{
    public function insertagent(Request $request)
    {
        if ($request->isMethod('post'))
        {
            $validator = Validator::make($request->all(),
            [
                'payment_id' => 'required|integer',
                'name' => 'required|unique:agents|max:30',
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

                //making agent
                $data = [
                    'payment_id' => $payment_id,
                    'name' => $name,
                    'percentage' => $percentage
                ];
                $insert = agent::create($data);
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

    public function updateagent($id, Request $request)
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

                //updating old agent
                $oldagent = agent::where('id','=',$id);
                $data = [
                    'payment_id' => $payment_id,
                    'name' => $name,
                    'percentage' => $percentage
                ];
                $insertagent = $oldagent -> update($data);
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
        $getPost = Agent::leftjoin('payments', 'payments.id', '=', 'agents.payment_id')
        ->addselect('agents.name as Nama_Agen', 'percentage as Komisi', 'payments.information as Metode_Pembayaran')
        ->OrderBy("agents.id", "ASC")
        ->get();

        $out = [
            "message" => "List - Agent",
            "results" => $getPost,
            "code" => 200
        ];
        return response()->json($out, $out['code']);
    }

    public function destroy($id)
    {
        $agent =  Agent::where('id','=',$id)->first();

        if (!$agent) {
            $data = [
                "message" => "error / data not found",
                "code" => 200
            ];
        } else {
            $agent->delete();
            $data = [
                "message" => "DeleteAgent - Success",
                "code" => 200
            ];
        };
        return response()->json($data, $data['code']);
    }
}
