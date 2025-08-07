<?php

namespace App\Http\Controllers;

use App\Ingredient;
use App\Ingredient_stock;
use App\User;
use App\Order;
use App\Table;
use App\Product;
use App\Product_stock;
use App\Order_list;
use App\Product_formula;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB; //pake facades DB

class OrderController extends Controller
{
    public function insertorder(Request $request)
{
    if ($request->isMethod('post')) {

        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'merchant_id' => 'required|integer',
            'product_id' => 'required|array',
            'amount' => 'required|array',
            'table_id' => 'nullable|integer',
            'agent_id' => 'nullable|integer',
            'information' => 'nullable',
            'note' => 'nullable'
        ]);

        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first()], 200);
        }

        DB::beginTransaction();
        try {
            // Init
            $token = $request->input('token');
            $user_id = User::where('token', $token)->max('id');
            $merchant_id = $request->input('merchant_id');
            $table_id = $request->input('table_id');
            $agent_id = $request->input('agent_id');
            $information = $request->input('information');
            $note = $request->input('note');
            $product_ids = $request->input('product_id');
            $amounts = $request->input('amount');
            $product_count = count($product_ids);

            // Simulate total stock requirement
            $productStockNeeds = [];
            $ingredientNeeds = [];

            for ($i = 0; $i < $product_count; $i++) {
                $product = Product::find($product_ids[$i]);
                $qty = $amounts[$i];

                if ($product->hasstock) {
                    // Simpan total kebutuhan produk stock
                    if (!isset($productStockNeeds[$product->id])) {
                        $productStockNeeds[$product->id] = 0;
                    }
                    $productStockNeeds[$product->id] += $qty;
                }

                if ($product->isformula) {
                    $formulas = Product_formula::where('product_id', $product->id)->get();
                    foreach ($formulas as $formula) {
                        if (!isset($ingredientNeeds[$formula->ingredient_id])) {
                            $ingredientNeeds[$formula->ingredient_id] = 0;
                        }
                        $ingredientNeeds[$formula->ingredient_id] += ($formula->amount * $qty);
                    }
                }
            }

            // Check product stock
            foreach ($productStockNeeds as $productId => $totalNeed) {
                $stock = Product_stock::where('product_id', $productId)->value('amount');
                if ($stock < $totalNeed) {
                    DB::rollback();
                    $productName = Product::find($productId)->name ?? 'Produk Tidak Diketahui';
                    return response()->json(["message" => "Stok tidak mencukupi untuk $productName"], 200);
                }
            }

            // Check ingredient stock
            foreach ($ingredientNeeds as $ingredientId => $totalNeed) {
                $stock = Ingredient_stock::where('ingredient_id', $ingredientId)->value('amount');
                if ($stock < $totalNeed) {
                    DB::rollback();
                    $ingredientName = Ingredient::find($ingredientId)->name ?? 'Bahan Tidak Diketahui';
                    return response()->json(["message" => "Bahan tidak mencukupi untuk $ingredientName"], 200);
                }
            }

            // // Kurangi stok produk
            // foreach ($productStockNeeds as $productId => $totalNeed) {
            //     Product_stock::where('product_id', $productId)
            //         ->decrement('amount', $totalNeed);
            // }

            // // Kurangi stok bahan
            // foreach ($ingredientNeeds as $ingredientId => $totalNeed) {
            //     Ingredient_stock::where('ingredient_id', $ingredientId)
            //         ->decrement('amount', $totalNeed);
            // }

            // Update table status
            if ($table_id) {
                Table::where('id', $table_id)->update(['status' => 'NotAvailable']);
            }

            // Create order
            $order = Order::create([
                'merchant_id' => $merchant_id,
                'table_id' => $table_id,
                'user_id' => $user_id,
                'agent_id' => $agent_id,
                'status' => "OPEN",
                'information' => $information,
                'note' => $note
            ]);

            // Create order list
            for ($i = 0; $i < $product_count; $i++) {
                Order_list::create([
                    'order_id' => $order->id,
                    'merchant_id' => $merchant_id,
                    'product_id' => $product_ids[$i],
                    'user_id' => $user_id,
                    'order_list_status_id' => 1,
                    'amount' => $amounts[$i]
                ]);
            }

            DB::commit();
            return response()->json(["message" => "Order Telah Dibuat"], 200);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(["message" => $e->getMessage()], 200);
        }
    }
}

    public function index()
    {
        $table = Order::leftjoin('tables', 'tables.id', '=', 'orders.table_id')
        ->where('orders.status', "=", "OPEN")
        ->where('orders.table_id','!=',null)
        ->OrderBy("orders.id", "ASC")
        ->addselect('tables.id as id')
        ->addselect('tables.number','tables.extend')
        ->addselect('orders.id as order_id')
        ->addselect('orders.note as order_note')
        ->get();
        $gojek = Order::where('orders.status', "=", "OPEN")
        ->where('orders.agent_id','=',2)
        ->leftjoin('agents','agents.id','=','orders.agent_id')
        ->orderby('orders.id',"ASC")
        ->addselect('information')
        ->addselect('orders.id as order_id')
        ->addselect('orders.note as order_note')
        ->get();
        $grab = Order::where('orders.status', "=", "OPEN")
        ->where('orders.agent_id','=',3)
        ->leftjoin('agents','agents.id','=','orders.agent_id')
        ->orderby('orders.id',"ASC")
        ->addselect('information')
        ->addselect('orders.id as order_id')
        ->addselect('orders.note as order_note')
        ->get();
        $takeaway = Order::where('orders.status', "=", "OPEN")
        ->where('orders.agent_id','=',1)
        ->where('orders.information','!=',null)
        ->addselect('information')
        ->addselect('orders.id as order_id')
        ->addselect('orders.note as order_note')
        ->get();

        $out = [
            "table" => $table,
            "gojek" => $gojek ,
            "grab" => $grab,
            "take_away" => $takeaway
        ];
        return response()->json($out, 200);
    }
}
