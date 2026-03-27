<?php

namespace App\Http\Controllers\SCM\Employee;

use App\Http\Controllers\Controller;
use Inertia\Inertia;

class InboundController extends Controller
{
    public function inbound()
    {
        return Inertia::render('Dashboard/SCM/Employee/Inbound');
    }
}
