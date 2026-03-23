<?php

namespace App\Http\Controllers\scm\employee;

use App\Http\Controllers\Controller;
use Inertia\Inertia;

class InboundController extends Controller
{
    public function inbound()
    {
        return Inertia::render('Dashboard/SCM/Employee/inbound');
    }
}
