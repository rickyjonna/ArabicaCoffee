<?php

namespace Database\Seeders;

use App\Agent;
use App\Income_type;
use App\User;
use App\Partner;
use App\Payment;
use App\Product;
use App\Merchant;
use App\Order_list;
use App\Order_list_status;
use App\Product_category;
use App\Table;
use App\User_type;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        User::Create([
            'user_type_id' => '1',
            'merchant_id' => '1',
            'name' => 'Ricky Jonna M K',
            'password' => Hash::make('123456'),
            'address' => 'Jl.Jahe Raya No.70',
            'phone_number' => '01'
        ]);

        Merchant::Create([
            'name' => 'CikDitiro',
            'address' => 'Jln CikDitiro',
            'phone_number' => '0618360966'
        ]);

        User_type::Create([
            'information' => 'Admin'
        ]);
        User_type::Create([
            'information' => 'Supervisor'
        ]);
        User_type::Create([
            'information' => 'Waitress'
        ]);
        User_type::Create([
            'information' => 'Chef'
        ]);
        User_type::Create([
            'information' => 'Barista'
        ]);
        User_type::Create([
            'information' => 'Cashier'
        ]);

        Partner::Create([
            'owner' => 'Self',
            'percentage' => '0'
        ]);
        Partner::Create([
            'owner' => 'Nantulang',
            'percentage' => '50'
        ]);

        Product_category::create([
            'information' => 'Makanan'
        ]);
        Product_category::create([
            'information' => 'Minuman'
        ]);
        Product_category::create([
            'information' => 'Lainnya'
        ]);

        Payment::create([
            'information' => 'Cash',
            'discount' => 0
        ]);

        Payment::create([
            'information' => 'GOPAY',
            'discount' => 0
        ]);

        Payment::create([
            'information' => 'OVO',
            'discount' => 0
        ]);

        Agent::create([
            'payment_id' => 1,
            'name' => 'NoAgent',
            'percentage' => 0
        ]);
        Agent::create([
            'payment_id' => 2,
            'name' => 'GOFOOD',
            'percentage' => 20
        ]);
        Agent::create([
            'payment_id' => 3,
            'name' => 'GRABFOOD',
            'percentage' => 20
        ]);

        Product::create([
            'merchant_id' => '1',
            'partner_id' => '1',
            'product_category_id' => '1',
            'name' => 'Dummy Product',
            'price' => '15000',
            'discount' => '0',
            'editable' => '1',
            'isformula' => '0',
            'hasstock' => '0',
            'information' => 'DummyDummyDummy'
        ]);

        Table::create([
            'merchant_id' => 1,
            'number' => 1,
            'extend' => 0
        ]);

        Table::create([
            'merchant_id' => 1,
            'number' => 2,
            'extend' => 0
        ]);

        Table::create([
            'merchant_id' => 1,
            'number' => 1,
            'extend' => 1
        ]);

        Order_list_status::create([
            'information' => 'Waiting',
        ]);

        Order_list_status::create([
            'information' => 'On Process',
        ]);

        Order_list_status::create([
            'information' => 'Done',
        ]);

        Order_list_status::create([
            'information' => 'Served',
        ]);

        Income_type::create([
            'information' => 'Penjualan'
        ]);

        Income_type::create([
            'information' => 'Top Up'
        ]);
    }
}
