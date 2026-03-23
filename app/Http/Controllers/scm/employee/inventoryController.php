<?php

namespace App\Http\Controllers\scm\employee;

use App\Http\Controllers\Controller;
use Inertia\Inertia;

class InventoryController extends Controller
{
    public function inventory()
    {
        return Inertia::render('Dashboard/SCM/Employee/inventory');
    }
}
