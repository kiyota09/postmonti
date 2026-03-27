<?php

namespace App\Http\Controllers\SCM\Employee;

use App\Http\Controllers\Controller;
use Inertia\Inertia;

class VerificationController extends Controller
{
    public function verification()
    {
        return Inertia::render('Dashboard/SCM/Employee/Verification');
    }
}
