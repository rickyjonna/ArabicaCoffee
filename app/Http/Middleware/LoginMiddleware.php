<?php

namespace App\Http\Middleware;

use Closure;
use App\User;

class LoginMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        try{
            if ($request->input('token')) {
                $check =  User::where('token', $request->input('token'))->first();

                if (!$check) {
                    return response('Token Tidak Valid.', 401);
                } else {
                    return $next($request);
                }
            } else {
                return response('Silahkan Masukkan Token.', 401);
            }
        } catch (\exception $e) {
            $errorcode = $e->getcode();
            $errormessage = $e->getmessage();
            $errormessage = substr($errormessage, 23, 75); // harusnya lebih dinamis << karena erornya sekarang cuma buat mati lamput jadi gpp
            $out = [
                "message" => $errorcode."-".$errormessage,
                "code"    => 400
            ];
            return response()->json($out, $out['code']); 
        };
    }
}