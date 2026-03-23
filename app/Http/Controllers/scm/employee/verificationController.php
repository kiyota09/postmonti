<?php

namespace App\Http\Controllers\scm\employee;

use App\Http\Controllers\Controller;
use Inertia\Inertia;

class verificationController extends Controller
{
    public function verification()
    {
        return Inertia::render('Dashboard/SCM/Employee/verification');
    }
}
