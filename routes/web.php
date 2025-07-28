<?php

/** @var \Laravel\Lumen\Routing\Router $router */

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

//page
$router->get("/dashboard/{token}", "PageController@dashboard");
$router->get("/orderlistlist/{id}", "PageController@orderlistlist");
$router->get("/kitchen", "PageController@kitchen");
$router->get("/cashier", "PageController@cashier");
$router->get("/product", "PageController@product");
$router->get("/addproduct", "PageController@addproduct");
$router->get("/productcategory", "PageController@productcategory");
$router->get("/agent", "PageController@agent");
$router->get("/partner", "PageController@partner");
$router->get("/table", "PageController@table");
$router->get("/ingredient", "PageController@ingredient");
$router->get("/payment", "PageController@payment");
$router->get("/user", "PageController@user");
//user
$router->post("/register", "AuthController@register");
$router->post("/login", "AuthController@login");
$router->post("/logout", "AuthController@logout");
$router->get("/listuser", "UserController@index");
$router->post("/updatepassword", "AuthController@updatepassword");
$router->post("/updateuser/{id}", "UserController@update");
$router->post("/deleteuser/{id}", "UserController@destroy");
//product
$router->post("/insertproduct", "ProductController@insertproduct");
$router->get("/listproduct", "ProductController@index");
$router->get("/listproduct/{categoryid}", "ProductController@indexbycategoryid");
$router->get("/listproductalert", "ProductController@indexalert");
$router->post("/updateproduct/{id}", "ProductController@updateproduct");
$router->post("/deleteproduct/{id}", "ProductController@destroy");
//ingredient
$router->get("/listingredient", "IngredientController@index");
$router->post("/insertingredient", "IngredientController@insertingredient");
$router->post("/updateingredient/{id}", "IngredientController@updateingredient");
$router->post("/deleteingredient/{id}", "IngredientController@destroy");
//stock
$router->get("/liststock", "ProductStockController@index");
$router->post("/updatestock/{id}", "ProductStockController@updatestock");
$router->delete("/deletestock/{id}", "ProductStockController@destroy");
//formula
$router->get("/listformula", "ProductFormulaController@index");
$router->patch("/updateformula/{id}", "ProductFormulaController@updateformula");
$router->delete("/deleteformula/{id}", "ProductFormulaController@destroy");
//table
$router->post("/inserttable", "TableController@inserttable");
$router->get("/listtable", "TableController@index");
$router->post("/updatetable/{id}", "TableController@updatetable");
$router->post("/deletetable/{id}", "TableController@destroy");
//product category
$router->post("/insertproductcategory", "ProductCategoryController@insertproductcategory");
$router->get("/listproductcategory", "ProductCategoryController@index");
$router->post("/updateproductcategory/{id}", "ProductCategoryController@updateproductcategory");
$router->post("/deleteproductcategory/{id}", "ProductCategoryController@destroy");
//order
$router->post("/insertorder", "OrderController@insertorder");
$router->get("/orderlist", "OrderController@index");
//order list
$router->post("/updateorderlist/{id}", "OrderListController@updateorderlist");
$router->get("/sameproductordereddetail/{id}", "OrderListController@sameproductordereddetail");
$router->post("/updateorderliststatus/{orderlist_id}", "OrderListController@updateorderliststatus");
$router->post("/updateolsbyproductid", "OrderListController@updateolsbyproductid");
$router->get("/deleteorderlist/{id}", "OrderListController@destroy");
//invoice
$router->post("/insertinvoice", "InvoiceController@insertinvoice");
$router->get("/checkinvoice/{invoice_id}", "InvoiceController@checkinvoice");
$router->post("/checkout/{invoice_id}", "InvoiceController@checkout");
$router->delete("/deleteinvoice/{id}", "InvoiceController@destroy");
$router->get('/invoicetoday', 'InvoiceController@indextoday');
//income -> terakhir
$router->get("/listincome", "IncomeController@index");
$router->delete("/deleteincome/{id}", "IncomeController@destroy");
//partner
$router->post("/insertpartner", "partnerController@insertpartner");
$router->get("/listpartner", "partnerController@index");
$router->post("/updatepartner/{id}", "partnerController@updatepartner");
$router->post("/deletepartner/{id}", "partnerController@destroy");
//payment
$router->post("/insertpayment", "PaymentController@insertpayment");
$router->get("/listpayment", "PaymentController@index");
$router->post("/updatepayment/{id}", "PaymentController@updatepayment");
$router->post("/deletepayment/{id}", "PaymentController@destroy");
//agent
$router->get("/listagent", "AgentController@index");
$router->post("/insertagent", "AgentController@insertagent");
$router->post("/updateagent/{id}", "AgentController@updateagent");
$router->post("/deleteagent/{id}", "AgentController@destroy");
