<?php

namespace App\Http\Controllers;

class TeacherController extends Controller
{
    public function index()
    {
        return redirect()->route('teacher.points');
    }

    public function points()
    {
        return view('teacher.points');
    }
}
