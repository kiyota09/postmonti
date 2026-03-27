<?php

namespace App\Http\Controllers\ECO\Staff;

use App\Http\Controllers\Controller;
use Inertia\Inertia;

class CustomerController extends Controller
{
    public function customer()
    {
        return Inertia::render('Dashboard/ECO/Employee/Customer');
    }
}
