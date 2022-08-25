<?php

namespace App\Http\Controllers;

use App\User;
use App\Product_stock;
use App\Ingredient_stock;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use Illuminate\Support\MessageBag;
use Validator, Input, Redirect;

class AuthController extends Controller
{

    public function register(Request $request)
    {
        try{
            $validator = Validator::make($request->all(), [
                'user_type_id' => 'bail|required|max:11',
                'merchant_id' => 'bail|required|max:11',
                'phone_number' => 'bail|required|unique:users|max:255',
                'password' => 'bail|required|min:6',
                'name' => 'bail|required|max:255',
                'address' => 'bail|required|max:255'
            ]);
            $messages = $validator->errors();
            if ($validator->fails()) {
                $out = [
                    "message" => $messages->first(),
                    "code"   => 200
                ];
                return response()->json($out, $out['code']);
            };
            //initialize
            $user_type_id = $request->input("user_type_id");
            $merchant_id = $request->input("merchant_id");
            $phone_number = $request->input("phone_number");
            $password = $request->input("password");
            $name = $request->input("name");
            $address = $request->input("address");
            $hashPwd = Hash::make($password);
            $data = [
                "user_type_id" => $user_type_id,
                "merchant_id" => $merchant_id,
                "phone_number" => $phone_number,
                "password" => $hashPwd,
                "name" => $name,
                "address" => $address
            ];
            User::create($data);
            $out = [
                "message" => "Register - Success",
                "code"    => 200
            ];
            return response()->json($out, $out['code']);

        } catch (\exception $e){
            $errormessage = $e->getmessage();
            $errormessage = substr($errormessage, 22, 75); // harusnya lebih dinamis << karena erornya sekarang cuma buat mati lamput jadi gpp
            $out = [
                "message" => $errormessage
            ];
            return response()->json($out, 200); 
        };  
    }

    public function login(Request $request)
    {
        try{
            $validator = Validator::make($request->all(), [
                'phone_number' => 'required',
                'password' => 'required|min:6'
            ]);
            $messages = $validator->errors();
            if ($validator->fails()) {
                $out = [
                    "message" => $messages->first()
                ];
                return response()->json($out, 200);
            };

            $phone_number = $request->input("phone_number");
            $password = $request->input("password");

            $user = User::where("phone_number","=", $phone_number)->first();

            if (!$user) {
                $out = [
                    "message" => "User Tidak ditemukan"
                ];
                return response()->json($out, 200);
            }

            if (Hash::check($password, $user->password)) {
                $newtoken  = $this->generateRandomString();
                $user->update([
                    'token' => $newtoken
                ]);
                $out = [
                    "message" => "Login Berhasil",
                    "result"  => [
                        "token" => $newtoken
                    ]
                ];
            } else {
                $out = [
                    "message" => "Password Salah"
                ];
            };
            return response()->json($out, 200);
        }catch (\exception $e) {
            DB::rollback();
            $message = $e->getmessage();
            $errormessage = substr($errormessage, 22, 75);
            $out  = [
                "message" => $errormessage
            ];  
            return response()->json($out,200);
        };
    }

    public function updatepassword(Request $request)
    {
        try{
            $validator = Validator::make($request->all(), [
                'token' => 'required',
                'password' => 'required|min:6',
                'newpassword' => 'required|min:6'
            ]);
            $messages = $validator->errors();
            if ($validator->fails()) {
                $out = [
                    "message" => $messages->first()
                ];
                return response()->json($out, 200);
            };
            //initialize
            $token = $request->input('token'); 
            $user = User::where('token','=',$token)->first();
            $password = $request->input('password');
            $newpassword = $request->input('newpassword'); 
            $hashnewpwd = Hash::make($newpassword);         
            //updating 
            if(!$user){
                $out  = [
                    "message" => "Harap Login Ulang"                  
                ];
                return response()->json($out, 200);
            };
            if (Hash::check($password, $user->password)) {
            
                $user->update(['password' => $hashnewpwd]);
                $out = [
                    "message" => "Ubah Password Berhasil"
                ];
                return response()->json($out, 200);
            } else {
                $out = [
                    "message" => "Password Salah"
                ];
                return response()->json($out, 200);
            };
        } catch (\exception $e){
            $errormessage = $e->getmessage();
            $errormessage = substr($errormessage, 22, 75); // harusnya lebih dinamis << karena erornya sekarang cuma buat mati lamput jadi gpp
            $out = [
                "message" => $errormessage
            ];
            return response()->json($out, 200); 
        };  
    }

    public function logout(Request $request)
    {   
        $validator = Validator::make($request->all(), [
            'token' => 'required'
        ]);
        $messages = $validator->errors();
        if ($validator->fails()) {
            $out = [
                "message" => $messages->first()
            ];
            return response()->json($out, 200);
        };
        try{
            $check =  User::where('token', $request->input('token'))->first();
            if (!$check) {
                $out = [
                    "message" => "Anda Sudah Logout"
                ];
                return response()->json($out, 200); 
            } else {
                $emptytoken = [
                        "token" => null
                ];
                $deletetoken = User::where('token', $request->input('token'))->update($emptytoken);
                $out = [
                    "message" => "Logout - Success"
                    ];
                return response()->json($out, 200); 
            };
        } catch (\exception $e){
            $errormessage = $e->getmessage();
            $errormessage = substr($errormessage, 22, 75); // harusnya lebih dinamis << karena erornya sekarang cuma buat mati lamput jadi gpp
            $out = [
                "message" => $errormessage
            ];
            return response()->json($out, 200); 
        };  
    }

    function generateRandomString($length = 80)
    {
        $karakter = '012345678dssd9abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $panjang_karakter = strlen($karakter);
        $str = '';
        for ($i = 0; $i < $length; $i++) {
            $str .= $karakter[rand(0, $panjang_karakter - 1)];
        }
        return $str;
    }
}