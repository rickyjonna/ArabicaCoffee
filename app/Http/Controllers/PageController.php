<?php

namespace App\Http\Controllers;

use App\Agent;
use App\User;
use App\User_type;
use App\Invoice;
use App\Product_stock;
use App\Ingredient;
use App\Ingredient_stock;
use App\Order;
use App\Order_list;
use App\Partner;
use App\Payment;
use App\Product;
use App\Product_category;
use App\Table;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; //pake facades DB

class PageController extends Controller
{

    public function dashboard($token, Request $request) //request = token
    {
        //filter = token -> usertype
            $usertypeid = User::join('user_type', 'users.user_type_id', '=', 'user_type.id')
            ->where('users.token', '=', $token)
            ->max('user_type_id');
            $user = User::join('user_type', 'users.user_type_id', '=', 'user_type.id')
            ->where('users.token', '=', $token)
            ->select('users.id as user_id','user_type_id', 'name as user_name', 'information as user_type')
            ->get();
            $today_income = Invoice::where('status','=','PAID')
            ->whereraw('Date(updated_at) = CURDATE()')
            ->sum('total');
            $user_active = User::where('token', '!=', null)
            ->get('name');
            $product_minimum = Product_stock::leftjoin('products', 'products.id', '=', 'product_stock.product_id')
            ->whereraw('product_stock.amount <= product_stock.minimum_amount')
            ->select('products.id as id','products.name as name','product_stock.amount as quantity')
            ->get();
            $ingredient_minimum = Ingredient_stock::leftjoin('ingredients', 'ingredients.id', '=', 'ingredient_stock.ingredient_id')
            ->whereraw('ingredient_stock.amount <= ingredient_stock.minimum_amount')
            ->select('ingredients.id','ingredients.name as name','ingredient_stock.amount as quantity')
            ->get();


            //dashboard admin
            if ($usertypeid == 1){
                $out = [
                    "message" => "Page-Dashboard-Success",
                    "result" => [
                        "user" => $user,
                        "today_income" => $today_income,
                        "user_active" => $user_active,
                        "product" => $product_minimum,
                        "ingredient" => $ingredient_minimum
                    ]
                ];
                return response()->json($out, 200);
            } elseif ($usertypeid == 2){
                $out = [
                    "message" => "Page-Dashboard-Success",
                    "result" => [
                        "user" => $user,
                        "product" => $product_minimum,
                        "ingredient" => $ingredient_minimum
                    ]
                ];
                return response()->json($out, 200);
            } elseif ($usertypeid == 3){
                $out = [
                    "message" => "Page-Dashboard-Success",
                    "result" => [
                        "user" => $user,
                        "product" => $product_minimum,
                        "ingredient" => $ingredient_minimum
                    ]
                ];
                return response()->json($out, 200);
            } else {
                $out = [
                    "message" => "Token Expired"
                ];
                return response()->json($out, 200);
            };
    }

    public function orderlistlist($id)
    {
        $product_list = Product::leftjoin('product_stock', 'product_stock.product_id', '=', 'products.id')
            ->leftjoin('product_category', 'products.product_category_id', '=', 'product_category.id')
            ->addselect('products.id','products.name', 'products.price')
            ->addselect('discount')
            ->selectRaw('price - price*discount/100 as total_price')
            ->addselect(DB::raw('(CASE WHEN amount is null THEN null ELSE amount END) as total_stock'))
            ->addselect('product_category.information as category')
            ->where("products.editable", "=", "1")
            ->OrderBy("products.id", "ASC")
            ->get();

        $order_list_notdone = Order_list::where('order_id','=',$id)
            ->leftjoin('orders','orders.id','=','order_list.order_id')
            ->leftjoin('products','products.id','=','order_list.product_id')
            ->leftjoin('order_list_status','order_list_status.id','=','order_list.order_list_status_id')
            ->select('order_list.product_id','products.name as product_name','order_list.amount as product_total')
            ->selectRaw('price - price*discount/100 as product_totalprice')
            ->addselect('order_list_status.id as orderlist_status_id','order_list_status.information as orderlist_status', 'order_list.id as orderlist_id')
            ->get();

        $order_list_all = Order_list::where('order_id','=',$id)
            ->leftjoin('orders','orders.id','=','order_list.order_id')
            ->leftjoin('products','products.id','=','order_list.product_id')
            ->leftjoin('order_list_status','order_list_status.id','=','order_list.order_list_status_id')
            ->select('order_list.product_id','products.name as product_name','order_list.amount as product_total')
            ->selectRaw('price - price*discount/100 as product_totalprice')
            ->addselect('order_list_status.id as orderlist_status_id','order_list_status.information as orderlist_status', 'order_list.id as orderlist_id')
            ->get();

        $result = [
            "product_list" => $product_list,
            "order_list_notdone" => $order_list_notdone,
            "order_list_all" => $order_list_all
        ];
        $out = [
            "message" => "Page - OrderListList - Success",
            "results" => $result
        ];
        return response()->json($out, 200);
    }

    public function kitchen()
    {
        //listorder
        $table = Order::leftjoin('tables', 'tables.id', '=', 'orders.table_id')
        ->where('orders.status', "=", "OPEN")
        ->where('orders.agent_id','=',1)
        ->where('orders.table_id','!=',null)
        ->OrderBy("orders.id", "ASC")
        ->addselect('tables.id as table_id')
        ->addselect('tables.number as table_number','tables.extend as table_extend')
        ->addselect('orders.id as order_id')
        ->addselect('orders.note as order_note')
        ->get();
        // masih 2 agent
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

        //productordered list
        $productordered_list = Order_list::where('order_list.order_list_status_id','=',1) //??
        ->leftjoin('products','products.id','=','order_list.product_id')
        ->select('products.id as product_id','products.name as product_name',DB::raw('SUM(order_list.amount) as product_total'))
        ->groupBy('products.id','products.name')
        ->get();

        //out
        $order_list = [
            "table" => $table,
            "gojek" => $gojek ,
            "grab" => $grab,
            "takeaway" => $takeaway,
        ];
        $results = [
            "order_list" => $order_list,
            "productordered_list" => $productordered_list
        ];
        $out = [
            "message" => "Page - Kitchen - Success",
            "results" => $results
            ];
        return response()->json($out, 200);
    }

    public function cashier()
    {
        //listorder
        $table = Order::leftjoin('tables', 'tables.id', '=', 'orders.table_id')
        ->where('orders.table_id','!=',null)
        ->leftjoin('invoices','invoices.order_id', '=', 'orders.id')
        ->where(function($query){
            $query->where('invoices.status','=','UNPAID')
                  ->orWhere('invoices.status','=',null);
        })
        ->OrderBy("orders.id", "ASC")
        ->addselect('tables.id as table_id')
        ->addselect('tables.number as table_number','tables.extend as table_extend')
        ->addselect('orders.id as order_id')
        ->addselect('invoices.status')
        ->get();
        // masih 2 agent
        $gojek = Order::where('orders.agent_id','=',2)
        ->leftjoin('agents','agents.id','=','orders.agent_id')
        ->leftjoin('invoices','invoices.order_id', '=', 'orders.id')
        ->where(function($query){
            $query->where('invoices.status','=','UNPAID')
                  ->orWhere('invoices.status','=',null);
        })
        ->orderby('orders.id',"ASC")
        ->addselect('information')
        ->addselect('orders.id as order_id')
        ->get();
        $grab = Order::where('orders.agent_id','=',3)
        ->leftjoin('agents','agents.id','=','orders.agent_id')
        ->leftjoin('invoices','invoices.order_id', '=', 'orders.id')
        ->where(function($query){
            $query->where('invoices.status','=','UNPAID')
                  ->orWhere('invoices.status','=',null);
        })
        ->orderby('orders.id',"ASC")
        ->addselect('information')
        ->addselect('orders.id as order_id')
        ->get();
        $takeaway = Order::where('orders.agent_id','=',1)
        ->where('orders.information','!=',null)
        ->leftjoin('invoices','invoices.order_id', '=', 'orders.id')
        ->where(function($query){
            $query->where('invoices.status','=','UNPAID')
                  ->orWhere('invoices.status','=',null);
        })
        ->addselect('information')
        ->addselect('orders.id as order_id')
        ->get();
        $payment = Payment::select("id","information","discount")
        ->orderby('id', "DESC")
        ->get();

        //out
        $order_list = [
            "table" => $table,
            "gojek" => $gojek ,
            "grab" => $grab,
            "takeaway" => $takeaway,
            "payment" => $payment
        ];
        $out = [
            "message" => "Page - Cashier - Success",
            "results" => $order_list
            ];
        return response()->json($out, 200);
    }

    public function product(){
        $productlist = Product::leftjoin('product_stock', 'product_stock.product_id', '=', 'products.id')
        ->leftjoin('product_category', 'products.product_category_id', '=', 'product_category.id')
        ->addselect('products.id','products.name', 'products.price')
        ->addselect('discount')
        ->selectRaw('price - discount as total_price')
        ->addselect(DB::raw('(CASE WHEN amount is null THEN null ELSE amount END) as total_stock'))
        ->addselect(DB::raw('(CASE WHEN minimum_amount is null THEN null ELSE minimum_amount END) as minimum_stock'))
        ->addselect('product_category.information as category')
        ->where("products.editable", "=", "1")
        ->OrderBy("products.id", "ASC")
        ->get();

        $out = [
            "message" => "Page - Product - Success",
            "results" => $productlist
        ];
        return response()->json($out, 200);
    }

    public function addproduct(){

        $category = Product_category::select('id','information')
        ->get();
        $agent = Agent::select('id','name')
        ->get();
        $partner =  Partner::select('id', 'owner', 'percentage')
        ->get();

        $results = [
            "category" => $category,
            "agent" => $agent,
            "partner" => $partner
        ];

        $out = [
            "message" => "Page - AddProduct - Success",
            "results" => $results
        ];
        return response()->json($out, 200);
    }

    public function productcategory(){

        $productcategory = Product_category::get();

        $results = [
            "productcategory" => $productcategory
        ];

        $out = [
            "message" => "Page - ProductCategory - Success",
            "results" => $results
        ];
        return response()->json($out, 200);
    }

    public function agent(){

        $agent = Agent::leftjoin('payments','payments.id','=','agents.payment_id')
        ->select('agents.id','payment_id','payments.information as payment_information','name','percentage')
        ->get();
        $payment = Payment::select('id','information','discount')
        ->get();

        $results = [
            "agent" => $agent,
            "payment" => $payment
        ];

        $out = [
            "message" => "Page - Agent - Success",
            "results" => $results
        ];
        return response()->json($out, 200);
    }

    public function partner(){

        $partner = Partner::select('id','owner','percentage')
        ->get();

        $results = [
            "partner" => $partner
        ];

        $out = [
            "message" => "Page - Partner - Success",
            "results" => $results
        ];
        return response()->json($out, 200);
    }

    public function table(){

        $table = Table::select('id','number','extend')
        ->get();

        $results = [
            "table" => $table
        ];

        $out = [
            "message" => "Page - Table - Success",
            "results" => $results
        ];
        return response()->json($out, 200);
    }

    public function ingredient(){

        $ingredient = Ingredient::leftjoin('ingredient_stock','ingredients.id','=','ingredient_stock.ingredient_id')
        ->select('ingredients.id','ingredients.name','ingredients.unit','ingredient_stock.amount','ingredient_stock.minimum_amount')
        ->get();

        $results = [
            "ingredient" => $ingredient
        ];

        $out = [
            "message" => "Page - Ingredient - Success",
            "results" => $results
        ];
        return response()->json($out, 200);
    }

    public function payment(){

        $payment = Payment::select('id','information','discount')
        ->get();

        $results = [
            "payment" => $payment
        ];

        $out = [
            "message" => "Page - Payment - Success",
            "results" => $results
        ];
        return response()->json($out, 200);
    }

    public function user(){

        $user = User::leftjoin('user_type','users.user_type_id','=','user_type.id')
        ->select('users.id as user_id','users.user_type_id','user_type.information as user_type','users.phone_number','users.name','users.address','users.password')
        ->get();

        $user_type = User_type::get();

        $results = [
            "user" => $user,
            "user_type" => $user_type
        ];

        $out = [
            "message" => "Page - User - Success",
            "results" => $results
        ];
        return response()->json($out, 200);
    }
}
