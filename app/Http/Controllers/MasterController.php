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
}
