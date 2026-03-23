<?php

namespace App\Http\Controllers\scm\manager;

use App\Http\Controllers\Controller;
use Inertia\Inertia;

class CloseController extends Controller
{
    public function close()
    {
        return Inertia::render('Dashboard/SCM/Manager/close');
    }
}
