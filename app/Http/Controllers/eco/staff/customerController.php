<?php

namespace App\Http\Controllers\eco\staff;

use App\Http\Controllers\Controller;
use Inertia\Inertia;

class CustomerController extends Controller
{
    public function customer()
    {
        return Inertia::render('Dashboard/ECO/Employee/customer');
    }
}
