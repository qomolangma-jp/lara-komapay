<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class MasterController extends Controller
{
    public function index()
    {
        return view('master_admin.index');
    }

    public function users()
    {
        $users = \App\Models\User::all();
        return view('master_admin.users', compact('users'));
    }

    public function products()
    {
        return view('master_admin.products');
    }

    public function orders()
    {
        return view('master_admin.orders');
    }

    public function news()
    {
        return view('master_admin.news');
    }

    public function stats()
    {
        return view('master_admin.stats');
    }

    public function cart()
    {
        return view('master_admin.cart');
    }
}
