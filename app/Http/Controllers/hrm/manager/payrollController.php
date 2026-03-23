<?php

namespace App\Http\Controllers\hrm\manager;

use App\Http\Controllers\Controller;
use Inertia\Inertia;

class PayrollController extends Controller
{
    public function payroll()
    {
        // FIX: Added 'Dashboard/' to the path to match resources/js/Pages/Dashboard/HRM/Applicants/application.vue
        return Inertia::render('Dashboard/HRM/Manager/payroll');
    }
}
