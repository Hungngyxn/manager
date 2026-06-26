<?php

namespace App\Http\Controllers\Report;
use App\Http\Controllers\Controller;

class ReportController extends Controller
{
    public function index()
    {
        return view('pages.report.index');
    }
}
