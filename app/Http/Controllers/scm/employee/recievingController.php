<?php

namespace App\Http\Controllers\scm\employee;

use App\Http\Controllers\Controller;
use Inertia\Inertia;

class RecievingController extends Controller
{
    public function recieving()
    {
        return Inertia::render('Dashboard/SCM/Employee/recieving');
    }
}
