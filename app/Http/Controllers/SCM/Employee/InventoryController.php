<?php

namespace App\Http\Controllers\SCM\Employee;

use App\Http\Controllers\Controller;
use Inertia\Inertia;

class InventoryController extends Controller
{
    public function inventory()
    {
        return Inertia::render('Dashboard/SCM/Employee/Inventory');
    }
}
