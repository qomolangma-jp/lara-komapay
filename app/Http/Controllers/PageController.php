<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PageController extends Controller
{
    public function login()
    {
        return view('login');
    }

    public function master()
    {
        return view('master');
    }

    public function student()
    {
        return view('student');
    }
}
