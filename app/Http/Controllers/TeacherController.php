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

    public function pointsPersonalHistory()
    {
        return view('points.personal_history', [
            'pageTitle' => '先生ポイント履歴（個人）',
            'pointsIndexUrl' => '/teacher/points',
            'personalHistoryUrl' => '/teacher/points/history/personal',
            'periodHistoryUrl' => '/teacher/points/history/period',
        ]);
    }

    public function pointsPeriodHistory()
    {
        return view('points.period_history', [
            'pageTitle' => '先生ポイント履歴（日次 / 月次）',
            'pointsIndexUrl' => '/teacher/points',
            'personalHistoryUrl' => '/teacher/points/history/personal',
            'periodHistoryUrl' => '/teacher/points/history/period',
        ]);
    }
}
