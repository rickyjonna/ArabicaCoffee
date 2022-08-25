<?php

namespace App\Http\Controllers;

use App\Income;
use App\Invoice;
use App\Order_list;
use App\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; //pake facades DB

class IncomeController extends Controller
{
    public function __construct()
    {
        $this->middleware("login");
    }

    public function index()
    {
        $listincome = Income::
        get();

        $out = [
            "message" => "Success - List Income",
            "results" => $listincome ///PERCANTIK BUAT AKUNTANSI
        ];

        return response()->json($out, 200);
    }
    
    public function destroy($id)
    {
        $income =  Income::where('id','=',$id)->first();

        if (!$income) {
            $data = [
                "message" => "error / data not found",
            ];
        } else {
            $income->delete();
            $data = [
                "message" => "success deleted"
            ];
        };
        return response()->json($data, 200);
    }
}//endclass