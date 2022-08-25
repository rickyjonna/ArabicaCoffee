<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;//pake facades DB
use Validator, Input, Redirect;

class UserController extends Controller
{
    public function index()
    {
        $getListUser = User::select('name','phone_number','information')
        ->OrderBy("users.id", "DESC")
        ->leftjoin('user_type', 'user_type.id', '=', 'users.user_type_id')
        ->get();

        $out = [
            "message" => "List User",
            "results" => $getListUser
        ];
        return response()->json($out, 200);        
    }

    public function update($id, Request $request)
    {
        $validator = Validator::make($request->all(), 
        [
            'user_type_id' => 'required',
            'phone_number' => 'required',
            'name' => 'required',
            'address' => 'required'
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
            try {//initialize
                $user_type_id = $request->input('user_type_id');
                $phone_number = $request->input('phone_number');
                $name = $request->input('name');   
                $address = $request->input('address');   
                //updating 
                $olduser = User::where('id','=',$id)->first();
                $newdatauser = [
                    'user_type_id' => $user_type_id,
                    'phone_number' => $phone_number,             
                    'name' => $name,
                    'address' => $address      
                ];
                $update_user = $olduser -> update($newdatauser);
                DB::commit();
                $out  = [
                    "message" => "EditUser - Success",
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
    }

    public function destroy($id)
    {
        $user =  User::where('id','=',$id)->first();
        if (!$user) {
            $data = [
                "message" => "error / data not found",
                "code" => 200
            ];
        } else {
            $user->delete();
            $data = [
                "message" => "DeleteUser - Success",
                "code" => 200
            ];
        };
        return response()->json($data, $data['code']);
    }
}