<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class ReportsController extends Controller
{
    public function mappings()
    {
        return view('admin.reports.mappings');
    }
}
