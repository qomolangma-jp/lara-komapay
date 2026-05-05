<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SellerController extends Controller
{
    public function index()
    {
        return view('seller_admin.index');
    }

    public function help()
    {
        return view('seller_admin.help');
    }

    public function products()
    {
        return view('seller_admin.products');
    }

    public function orders()
    {
        return view('seller_admin.orders');
    }

    public function news()
    {
        return view('seller_admin.news');
    }

    public function reports()
    {
        return view('seller_admin.reports');
    }
}
