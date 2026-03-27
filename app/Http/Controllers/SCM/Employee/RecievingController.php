<?php

namespace App\Http\Controllers\SCM\Employee;

use App\Http\Controllers\Controller;
use Inertia\Inertia;

class RecievingController extends Controller
{
    public function recieving()
    {
        return Inertia::render('Dashboard/SCM/Employee/Recieving');
    }
}
