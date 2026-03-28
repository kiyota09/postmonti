<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Inertia\Inertia;

class SupportController extends Controller
{
    public function support()
    {
        // TODO: Replace dummy stats with actual DB queries
        return Inertia::render('Client/Support');
    }
}
