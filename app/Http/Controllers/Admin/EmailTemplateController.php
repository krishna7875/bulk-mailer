<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;

class EmailTemplateController extends Controller
{
    public function index()
    {
        return view('admin.templates.index');
    }
}
